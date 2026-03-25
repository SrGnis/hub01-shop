<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CollectionEntryReorderRequest;
use App\Http\Requests\Api\V1\CollectionEntryStoreRequest;
use App\Http\Requests\Api\V1\CollectionEntryUpdateNoteRequest;
use App\Http\Requests\Api\V1\CollectionHiddenShowRequest;
use App\Http\Requests\Api\V1\CollectionOwnerIndexRequest;
use App\Http\Requests\Api\V1\CollectionPublicIndexRequest;
use App\Http\Requests\Api\V1\CollectionQuickCreateRequest;
use App\Http\Requests\Api\V1\CollectionStoreRequest;
use App\Http\Requests\Api\V1\CollectionUpdateRequest;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Models\Project;
use App\Services\CollectionService;
use Illuminate\Support\Facades\Gate;

class CollectionController extends Controller
{
    public function __construct(private readonly CollectionService $collectionService)
    {
    }

    public function publicIndex(CollectionPublicIndexRequest $request)
    {
        $validated = $request->validated();

        $query = Collection::query()
            ->discoverable()
            ->with('user');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $orderBy = $validated['order_by'] ?? 'updated_at';
        $orderDirection = $validated['order_direction'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 10;

        $paginator = $query
            ->orderBy($orderBy, $orderDirection)
            ->orderBy('uid')
            ->paginate($perPage);

        return CollectionResource::collection($paginator);
    }

    public function publicShow(string $uid)
    {
        $collection = Collection::query()
            ->discoverable()
            ->where('uid', $uid)
            ->with(['user', 'entries.project'])
            ->firstOrFail();

        Gate::authorize('view', $collection);

        return CollectionResource::make($collection);
    }

    public function hiddenShow(CollectionHiddenShowRequest $request)
    {
        $token = $request->validated()['token'];

        $collection = Collection::query()
            ->hiddenToken($token)
            ->with(['user', 'entries.project'])
            ->firstOrFail();

        Gate::authorize('collections.view.hidden-token', [$collection, $token]);

        return CollectionResource::make($collection);
    }

    public function ownerIndex(CollectionOwnerIndexRequest $request)
    {
        $validated = $request->validated();

        $query = Collection::query()
            ->ownerVisible($request->user()->id)
            ->with('user');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (!empty($validated['visibility'])) {
            $query->where('visibility', $validated['visibility']);
        }

        $orderBy = $validated['order_by'] ?? 'updated_at';
        $orderDirection = $validated['order_direction'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 10;

        $paginator = $query
            ->orderBy($orderBy, $orderDirection)
            ->orderBy('uid')
            ->paginate($perPage);

        return CollectionResource::collection($paginator);
    }

    public function ownerShow(string $uid)
    {
        $collection = $this->resolveOwnerCollection($uid);

        Gate::authorize('view', $collection);

        return CollectionResource::make($collection->load(['user', 'entries.project']));
    }

    public function store(CollectionStoreRequest $request)
    {
        Gate::authorize('create', Collection::class);

        $collection = $this->collectionService->createCollection(
            $request->user(),
            $request->validated()
        )->load('user');

        return CollectionResource::make($collection)
            ->additional(['message' => 'Collection created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(CollectionUpdateRequest $request, string $uid)
    {
        $collection = $this->resolveOwnerCollection($uid);

        Gate::authorize('update', $collection);

        $collection = $this->collectionService
            ->updateCollection($collection, $request->validated())
            ->load('user');

        return CollectionResource::make($collection)
            ->additional(['message' => 'Collection updated successfully']);
    }

    public function destroy(string $uid)
    {
        $collection = $this->resolveOwnerCollection($uid);

        Gate::authorize('delete', $collection);

        try {
            $this->collectionService->deleteCollection($collection);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->noContent();
    }

    public function addEntry(CollectionEntryStoreRequest $request, string $uid)
    {
        $collection = $this->resolveOwnerCollection($uid);

        Gate::authorize('collections.manage.entries', $collection);

        $project = Project::query()
            ->accessScope()
            ->where('slug', $request->validated()['project'])
            ->firstOrFail();

        try {
            $this->collectionService->addEntry(
                $collection,
                $project,
                $request->validated()['note'] ?? null,
                $request->validated()['sort_order'] ?? null
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return CollectionResource::make($collection->load(['user', 'entries.project']))
            ->additional(['message' => 'Collection entry added successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function removeEntry(string $uid, string $entryUid)
    {
        $collection = $this->resolveOwnerCollection($uid);

        Gate::authorize('collections.manage.entries', $collection);

        try {
            $this->collectionService->removeEntry($collection, $entryUid);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->noContent();
    }

    public function updateEntryNote(CollectionEntryUpdateNoteRequest $request, string $uid, string $entryUid)
    {
        $collection = $this->resolveOwnerCollection($uid);

        Gate::authorize('collections.manage.entries', $collection);

        try {
            $this->collectionService->updateEntryNote(
                $collection,
                $entryUid,
                $request->validated()['note'] ?? null
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return CollectionResource::make($collection->load(['user', 'entries.project']))
            ->additional(['message' => 'Collection entry note updated successfully']);
    }

    public function reorderEntries(CollectionEntryReorderRequest $request, string $uid)
    {
        $collection = $this->resolveOwnerCollection($uid);

        Gate::authorize('collections.manage.entries', $collection);

        try {
            $this->collectionService->reorderEntries($collection, $request->validated()['entry_uids']);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return CollectionResource::make($collection->load(['user', 'entries.project']))
            ->additional(['message' => 'Collection entries reordered successfully']);
    }

    public function quickCreateAndAttach(CollectionQuickCreateRequest $request)
    {
        Gate::authorize('create', Collection::class);

        $project = Project::query()
            ->accessScope()
            ->where('slug', $request->validated()['project'])
            ->firstOrFail();

        try {
            $collection = $this->collectionService->quickCreatePrivateCollectionAndAttachProject(
                $request->user(),
                $request->validated()['name'],
                $project,
                $request->validated()['description'] ?? null,
                $request->validated()['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return CollectionResource::make($collection->load('user'))
            ->additional(['message' => 'Collection created and project attached successfully'])
            ->response()
            ->setStatusCode(201);
    }

    private function resolveOwnerCollection(string $uid): Collection
    {
        return Collection::query()
            ->where('uid', $uid)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();
    }
}

