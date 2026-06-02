<?php

namespace App\Livewire\Platform;

use App\Services\CollectionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;

class Collections extends Component
{
    use WithPagination;

    #[Session(key: 'platform-collections-search')]
    public string $search = '';

    #[Session(key: 'platform-collections-visibility')]
    public string $visibility = 'all';

    #[Session(key: 'platform-collections-per-page')]
    public int $perPage = 10;

    private CollectionService $collectionService;

    public function boot(CollectionService $collectionService): void
    {
        $this->collectionService = $collectionService;
    }

    #[Computed]
    public function collections(): LengthAwarePaginator
    {
        return $this->collectionService->paginateForOwner(
            user: Auth::user(),
            search: trim($this->search) ?: null,
            visibility: $this->visibility,
            perPage: $this->perPage,
            withUser: true,
            withEntriesCount: true,
            withProjectsCount: true,
        );
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function visibilityOptions(): array
    {
        return $this->collectionService->visibilityOptions();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedVisibility(): void
    {
        $this->resetPage();
    }

    public function updatedOrder(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.platform.collections');
    }
}
