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

class CollectionService
{
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
