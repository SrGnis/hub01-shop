<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class NotificationManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $typeFilter = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];
    public int $perPage = 20;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'sortBy' => ['except' => ['column' => 'created_at', 'direction' => 'desc']],
    ];

    public function mount(): void
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortBy['column'] === $field) {
            $this->sortBy['direction'] = $this->sortBy['direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy['direction'] = 'asc';
        }

        $this->sortBy['column'] = $field;
    }

    public function getNotificationTypes(): array
    {
        $types = DB::table('notifications')
            ->select('type')
            ->where('notifiable_type', 'App\\Models\\User')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->toArray();

        return array_combine($types, array_map(fn ($type) => $this->formatNotificationType($type), $types));
    }

    protected function formatNotificationType(string $type): string
    {
        return str_replace('App\\Notifications\\', '', $type);
    }

    protected function getNotificationDataPreview(string $data): string
    {
        $decoded = json_decode($data, true);

        return Str::limit(json_encode($decoded, JSON_PRETTY_PRINT), 200);
    }

    public function render(): View
    {
        $query = DB::table('notifications')
            ->join('users', 'notifications.notifiable_id', '=', 'users.id')
            ->where('notifications.notifiable_type', 'App\\Models\\User')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('users.name', 'like', '%'.$this->search.'%')
                        ->orWhere('users.email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->dateFrom, function ($query) {
                $query->where('notifications.created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->where('notifications.created_at', '<=', $this->dateTo);
            })
            ->when($this->typeFilter, function ($query) {
                $query->where('notifications.type', $this->typeFilter);
            });

        // Handle sorting for relationship fields
        if ($this->sortBy['column'] === 'user.name') {
            $query->orderBy('users.name', $this->sortBy['direction']);
        } else {
            $query->orderBy('notifications.'.$this->sortBy['column'], $this->sortBy['direction']);
        }

        $notifications = $query->select('notifications.*', 'users.name as user_name', 'users.email as user_email')
            ->paginate($this->perPage);

        return view('livewire.admin.notification-management', [
            'notifications' => $notifications,
            'notificationTypes' => $this->getNotificationTypes(),
        ]);
    }
}
