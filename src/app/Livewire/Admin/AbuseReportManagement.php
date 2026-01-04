<?php

namespace App\Livewire\Admin;

use App\Models\AbuseReport;
use App\Services\AbuseReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class AbuseReportManagement extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'sortBy' => ['except' => ['column' => 'created_at', 'direction' => 'desc']],
    ];

    public function mount(): void
    {
        if(!Auth::user()->isAdmin()){
            abort(403);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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

    public function markAsResolved(int $id): void
    {
        $report = AbuseReport::findOrFail($id);
        $this->authorize('update', $report);

        $service = app(AbuseReportService::class);
        $service->resolveReport($report);

        session()->flash('message', 'Report marked as resolved.');
    }

    public function markAsPending(int $id): void
    {
        $report = AbuseReport::findOrFail($id);
        $this->authorize('update', $report);

        $service = app(AbuseReportService::class);
        $service->reopenReport($report);

        session()->flash('message', 'Report reopened.');
    }

    public function deleteReport(int $id): void
    {
        $report = AbuseReport::findOrFail($id);
        $this->authorize('delete', $report);

        $report->delete();

        session()->flash('message', 'Report deleted.');
    }

    public function render(): View
    {
        $query = AbuseReport::with(['reporter', 'reportable'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('reason', 'like', '%' . $this->search . '%')
                        ->orWhereHas('reporter', function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->typeFilter, function ($query) {
                $query->where('reportable_type', $this->typeFilter);
            });

        // Handle sorting for relationship fields
        if ($this->sortBy['column'] === 'reporter.name') {
            $query->select('abuse_reports.*')
                  ->join('users', 'abuse_reports.reporter_id', '=', 'users.id')
                  ->orderBy('users.name', $this->sortBy['direction']);
        } else {
            $query->orderBy($this->sortBy['column'], $this->sortBy['direction']);
        }

        $reports = $query->paginate(20);

        return view('livewire.admin.abuse-report-management', [
            'reports' => $reports,
        ]);
    }
}
