<?php

namespace App\Services;

use App\Enums\CollectionSystemType;
use App\Enums\CollectionVisibility;
use App\Models\Collection;
use App\Models\CollectionEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CollectionService
{
    private const ALLOWED_ORDER_COLUMNS = ['name', 'created_at', 'updated_at'];
    /**
     * Paginate public discoverable collections.
     */
    public function paginatePublic(
        ?string $search = null,
        string $orderBy = 'updated_at',
        string $orderDirection = 'desc',
        int $perPage = 10,
    ): LengthAwarePaginator {
        $orderBy = in_array($orderBy, self::ALLOWED_ORDER_COLUMNS, true) ? $orderBy : 'updated_at';
        $orderDirection = strtolower($orderDirection) === 'asc' ? 'asc' : 'desc';

        return Collection::query()
            ->discoverable()
            ->search($search)
            ->with('user')
            ->orderBy($orderBy, $orderDirection)
            ->orderBy('uid')
            ->paginate($perPage);
    }

    /**
     * Paginate collections owned by a user.
     * Used by Platform dashboard, API owner index, and UserProfile.
     */
    public function paginateForOwner(
        User $user,
        ?string $search = null,
        string $visibility = 'all',
        string $orderBy = 'updated_at',
        string $orderDirection = 'desc',
        int $perPage = 10,
        bool $excludeSystem = true,
        bool $withUser = false,
        bool $withEntriesCount = false,
        bool $withEntriesProject = false,
        bool $withProjectsCount = false,
    ): LengthAwarePaginator {
        $query = Collection::query()
            ->ownerVisible($user->id)
            ->search($search)
            ->withVisibility($visibility);

        if ($excludeSystem) {
            $query->nonSystem();
        }

        if ($withUser) {
            $query->with('user');
        }

        if ($withEntriesCount) {
            $query->withCount('entries');
        }

        if ($withEntriesProject) {
            $query->with(['entries.project:id,name,logo_path']);
        }

        if ($withProjectsCount) {
            $query->addSelect([
                'projects_count' => DB::table('collection_entry')
                    ->selectRaw('COUNT(DISTINCT collection_entry.project_id)')
                    ->whereColumn('collection_entry.collection_uid', 'collection.uid'),
            ]);
        }

        $orderBy = in_array($orderBy, self::ALLOWED_ORDER_COLUMNS, true) ? $orderBy : 'updated_at';
        $orderDirection = strtolower($orderDirection) === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($orderBy, $orderDirection)
            ->orderBy('uid')
            ->paginate($perPage);
    }

    /**
     * Get a single collection by UID for a specific owner.
     */
    public function getByUidForUser(string $uid, User $user, bool $excludeSystem = false): ?Collection
    {
        $query = Collection::query()
            ->where('uid', $uid)
            ->where('user_id', $user->id);

        if ($excludeSystem) {
            $query->nonSystem();
        }

        return $query->first();
    }

    /**
     * Get a single collection by UID with standard eager loads.
     */
    public function getByUidWithEntries(string $uid): ?Collection
    {
        return Collection::query()
            ->where('uid', $uid)
            ->with(['user'])
            ->first();
    }

    /**
     * Get a single discoverable (public, non-favorites) collection by UID.
     */
    public function getDiscoverableByUid(string $uid): ?Collection
    {
        return Collection::query()
            ->discoverable()
            ->where('uid', $uid)
            ->with(['user', 'entries.project'])
            ->first();
    }

    /**
     * Get a hidden collection by share token.
     */
    public function getHiddenByToken(string $token): ?Collection
    {
        return Collection::query()
            ->hiddenToken($token)
            ->with(['user'])
            ->first();
    }

    /**
     * Get the favorites collection for a user.
     */
    public function getFavoritesForUser(User $user): ?Collection
    {
        return Collection::query()
            ->where('user_id', $user->id)
            ->where('system_type', CollectionSystemType::FAVORITES)
            ->withCount('entries')
            ->with(['entries.project:id,name,logo_path'])
            ->first();
    }

    /**
     * Check if a project exists in any of a user's non-system collections.
     */
    public function isInAnyCollection(User $user, Project $project): bool
    {
        return Collection::query()
            ->where('user_id', $user->id)
            ->nonSystem()
            ->whereHas('entries', function (Builder $query) use ($project): void {
                $query->where('project_id', $project->id);
            })
            ->exists();
    }

    /**
     * Get non-system collections for a user (for dropdowns).
     * Optionally checks if a specific project is already in each collection.
     */
    public function getAvailableForUser(User $user, ?int $targetProjectId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Collection::query()
            ->where('user_id', $user->id)
            ->nonSystem();

        if ($targetProjectId !== null) {
            $query->withExists([
                'entries as includes_target_project' => function ($entryQuery) use ($targetProjectId): void {
                    $entryQuery->where('project_id', $targetProjectId);
                },
            ]);
        }

        return $query
            ->orderBy('updated_at', 'desc')
            ->orderBy('uid')
            ->get();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function visibilityOptions(): array
    {
        return [
            ['id' => 'all', 'name' => 'All visibility'],
            ['id' => CollectionVisibility::PUBLIC->value, 'name' => 'Public'],
            ['id' => CollectionVisibility::PRIVATE->value, 'name' => 'Private'],
            ['id' => CollectionVisibility::HIDDEN->value, 'name' => 'Hidden'],
        ];
    }

    /**
     * Ensure a single valid favorites collection exists for a user.
     */
    public function getOrCreateFavoritesCollection(User $user): Collection
    {
        $collection = Collection::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'system_type' => CollectionSystemType::FAVORITES,
            ],
            [
                'name' => 'Favorites',
                'description' => null,
                'visibility' => CollectionVisibility::PRIVATE,
                'hidden_share_token' => null,
            ]
        );

        // Enforce favorites invariants on every access path.
        if ($collection->visibility !== CollectionVisibility::PRIVATE || $collection->hidden_share_token !== null) {
            $collection->update([
                'visibility' => CollectionVisibility::PRIVATE,
                'hidden_share_token' => null,
            ]);

            $collection->refresh();
        }

        return $collection;
    }

    /**
     * Toggle a project's presence in favorites.
     *
     * @return array{favorited:bool,collection:Collection}
     */
    public function toggleFavorite(User $user, Project|int $project): array
    {
        $projectId = $project instanceof Project ? $project->id : $project;
        $favorites = $this->getOrCreateFavoritesCollection($user);

        $changedToFavorited = DB::transaction(function () use ($favorites, $projectId): bool {
            $existing = $favorites->entries()
                ->where('project_id', $projectId)
                ->first();

            if ($existing) {
                $existing->delete();

                $this->normalizeSortOrder($favorites);

                return false;
            }

            $nextSortOrder = (int) ($favorites->entries()->max('sort_order') ?? -1) + 1;

            $favorites->entries()->create([
                'project_id' => $projectId,
                'note' => null,
                'sort_order' => $nextSortOrder,
            ]);

            return true;
        });

        return [
            'favorited' => $changedToFavorited,
            'collection' => $favorites,
        ];
    }

    /**
     * Check if project is favorited by user.
     */
    public function isFavorited(User $user, Project|int $project): bool
    {
        $projectId = $project instanceof Project ? $project->id : $project;
        $favorites = $this->getOrCreateFavoritesCollection($user);

        return $favorites->entries()->where('project_id', $projectId)->exists();
    }

    /**
     * Create a user collection.
     */
    public function createCollection(User $user, array $data): Collection
    {
        $visibility = $this->resolveVisibility($data['visibility'] ?? CollectionVisibility::PRIVATE);

        return Collection::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'visibility' => $visibility,
            'hidden_share_token' => $visibility === CollectionVisibility::HIDDEN
                ? $this->generateHiddenShareToken()
                : null,
        ]);
    }

    /**
     * Update collection metadata and visibility semantics.
     */
    public function updateCollection(Collection $collection, array $data): Collection
    {
        $payload = [];

        if (array_key_exists('name', $data)) {
            $payload['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }

        if (array_key_exists('visibility', $data)) {
            $payload['visibility'] = $this->resolveVisibility($data['visibility']);
        }

        if ($collection->isFavoritesSystemCollection()) {
            $payload['visibility'] = CollectionVisibility::PRIVATE;
            $payload['hidden_share_token'] = null;
        } else {
            $effectiveVisibility = $payload['visibility'] ?? $collection->visibility;

            if ($effectiveVisibility === CollectionVisibility::HIDDEN) {
                $payload['hidden_share_token'] = $collection->hidden_share_token ?: $this->generateHiddenShareToken();
            } else {
                $payload['hidden_share_token'] = null;
            }
        }

        if (!empty($payload)) {
            $collection->update($payload);
            $collection->refresh();
        }

        return $collection;
    }

    /**
     * Delete a non-system collection.
     */
    public function deleteCollection(Collection $collection): void
    {
        if ($collection->isFavoritesSystemCollection()) {
            throw new \RuntimeException('Favorites collection cannot be deleted.');
        }

        if ($collection->isSystem()) {
            throw new \RuntimeException('System collection cannot be deleted.');
        }

        $collection->delete();
    }

    /**
     * Add a project entry into a collection.
     */
    public function addEntry(Collection $collection, Project|int $project, ?string $note = null, ?int $sortOrder = null): CollectionEntry
    {
        $projectId = $project instanceof Project ? $project->id : $project;

        if ($collection->entries()->where('project_id', $projectId)->exists()) {
            throw new \RuntimeException('Project already exists in this collection.');
        }

        $nextSortOrder = (int) ($collection->entries()->max('sort_order') ?? -1) + 1;

        return $collection->entries()->create([
            'project_id' => $projectId,
            'note' => $note,
            'sort_order' => $sortOrder ?? $nextSortOrder,
        ]);
    }

    /**
     * Remove an entry from a collection.
     */
    public function removeEntry(Collection $collection, CollectionEntry|string $entry): void
    {
        $entryModel = $this->resolveEntry($collection, $entry);
        $entryModel->delete();

        $this->normalizeSortOrder($collection);
    }

    /**
     * Update a collection entry note.
     */
    public function updateEntryNote(Collection $collection, CollectionEntry|string $entry, ?string $note): CollectionEntry
    {
        $entryModel = $this->resolveEntry($collection, $entry);
        $entryModel->update(['note' => $note]);

        return $entryModel->refresh();
    }

    /**
     * Reorder collection entries by entry UID.
     *
     * @param  array<int, string>  $entryUids
     */
    public function reorderEntries(Collection $collection, array $entryUids): void
    {
        $existingUids = $collection->entries()->pluck('uid')->all();

        sort($existingUids);
        $providedUids = array_values(array_unique($entryUids));
        sort($providedUids);

        if ($existingUids !== $providedUids) {
            throw new \InvalidArgumentException('Entry reorder payload must include each collection entry exactly once.');
        }

        DB::transaction(function () use ($collection, $entryUids): void {
            foreach (array_values($entryUids) as $index => $entryUid) {
                $collection->entries()
                    ->where('uid', $entryUid)
                    ->update(['sort_order' => $index]);
            }
        });
    }

    /**
     * Quickly create a private collection and attach a project.
     */
    public function quickCreatePrivateCollectionAndAttachProject(
        User $user,
        string $name,
        Project|int $project,
        ?string $description = null,
        ?string $note = null
    ): Collection {
        $collection = DB::transaction(function () use ($user, $name, $project, $description, $note): Collection {
            $collection = $this->createCollection($user, [
                'name' => $name,
                'description' => $description,
                'visibility' => CollectionVisibility::PRIVATE,
            ]);

            $this->addEntry($collection, $project, $note);

            return $collection;
        });

        return $collection->load('entries.project');
    }

    /**
     * Resolve and validate a visibility value.
     */
    private function resolveVisibility(CollectionVisibility|string $visibility): CollectionVisibility
    {
        if ($visibility instanceof CollectionVisibility) {
            return $visibility;
        }

        return CollectionVisibility::fromString($visibility);
    }

    /**
     * Resolve and verify an entry belongs to the collection.
     */
    private function resolveEntry(Collection $collection, CollectionEntry|string $entry): CollectionEntry
    {
        $entryModel = $entry instanceof CollectionEntry
            ? $entry
            : CollectionEntry::query()->where('uid', $entry)->firstOrFail();

        if ($entryModel->collection_uid !== $collection->uid) {
            throw new \RuntimeException('Entry does not belong to the provided collection.');
        }

        return $entryModel;
    }

    /**
     * Keep deterministic contiguous sort orders.
     */
    private function normalizeSortOrder(Collection $collection): void
    {
        $uids = $collection->entries()->ordered()->pluck('uid')->all();

        DB::transaction(function () use ($collection, $uids): void {
            foreach ($uids as $index => $uid) {
                $collection->entries()
                    ->where('uid', $uid)
                    ->update(['sort_order' => $index]);
            }
        });
    }

    /**
     * Generate a unique hidden-share token.
     */
    private function generateHiddenShareToken(): string
    {
        do {
            $token = Str::ulid()->toBase32();
        } while (Collection::query()->where('hidden_share_token', $token)->exists());

        return $token;
    }
}
