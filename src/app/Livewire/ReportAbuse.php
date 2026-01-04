<?php

namespace App\Livewire;

use App\Models\AbuseReport;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use App\Services\AbuseReportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class ReportAbuse extends Component
{
    use Toast;

    public $showModal = false;
    public $reason = '';
    public $reportedItemId = null;
    public $reportedItemType = null;
    public $reportedItemName = '';
    public $reportedItemTypeLabel = '';

    #[On('open-report-modal')]
    public function openModal($itemId, $itemType, $itemName = null)
    {
        // Validate that user is authenticated and verified
        if (!Auth::check()) {
            session()->flash('error', 'You must be logged in to submit a report.');
            $this->redirectRoute('login');
            return;
        }

        $user = Auth::user();

        // Check if user is verified
        if (!$user->email_verified_at) {
            session()->flash('error', 'You must be a verified user to submit a report.');
            $this->redirectRoute('verification.notice');
            return;
        }
        // Ensure the itemId is cast to integer to avoid string mismatch
        $this->reportedItemId = (int) $itemId;
        $this->reportedItemType = $itemType;
        $this->reportedItemName = $itemName ?? $this->getItemName($itemType, $this->reportedItemId);
        $this->reportedItemTypeLabel = $this->getItemTypeLabel($itemType);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['reason', 'reportedItemId', 'reportedItemType', 'reportedItemName', 'reportedItemTypeLabel']);
    }

    public function submitReport()
    {
        // Validate that user is authenticated and verified
        if (!Auth::check()) {
            $this->error('You must be logged in to submit a report.');
            return;
        }

        $user = Auth::user();

        // Check if user is verified
        if (!$user->email_verified_at) {
            $this->error('You must be a verified user to submit a report.');
            return;
        }

        $this->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $data = [
            'reason' => $this->reason,
            'reportable_id' => $this->reportedItemId,
            'reportable_type' => $this->reportedItemType,
            'reporter_id' => $user->id,
        ];

        try {
            $abuseReportService = new AbuseReportService();
            $report = $abuseReportService->createReport($data);

            $this->success('Report submitted successfully. Our team will review it shortly.');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function getItemName(string $itemType, int $itemId): string
    {
        return match($itemType) {
            Project::class => Project::find($itemId)?->name ?? 'Unknown Project',
            ProjectVersion::class => ProjectVersion::find($itemId)?->name ?? 'Unknown Version',
            User::class => User::find($itemId)?->name ?? 'Unknown User',
            default => 'Unknown Item',
        };
    }

    private function getItemTypeLabel(string $itemType): string
    {
        return match($itemType) {
            Project::class => 'Project',
            ProjectVersion::class => 'Project Version',
            User::class => 'User',
            default => 'Item',
        };
    }

    public function render()
    {
        return view('livewire.report-abuse');
    }
}
