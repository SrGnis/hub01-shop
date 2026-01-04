<?php

namespace App\Services;

use App\Models\AbuseReport;
use App\Models\User;
use App\Notifications\AbuseReportCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AbuseReportService
{
    /**
     * Create a new abuse report.
     *
     * Validates that the reporter is authenticated and verified.
     * Validates that the reported item exists.
     *
     * @param  array  $data  Report data: reporter_id, reason, reportable_id, reportable_type
     * @return AbuseReport
     * @throws \Exception If validation fails
     */
    public function createReport(array $data): AbuseReport
    {
        $reporter = User::findOrFail($data['reporter_id']);
        $this->validateReporter($reporter);

        // Validate reported item exists
        $this->validateReportedItem($data['reportable_type'], $data['reportable_id']);

        return DB::transaction(function () use ($data, $reporter) {
            $report = AbuseReport::create([
                'reason' => $data['reason'],
                'reportable_id' => $data['reportable_id'],
                'reportable_type' => $data['reportable_type'],
                'reporter_id' => $data['reporter_id'],
                'status' => 'pending',
            ]);

            // Send notification to all admin users
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AbuseReportCreated($report, $reporter));
            }

            return $report;
        });
    }

    /**
     * Get all abuse reports for admin review.
     *
     * @param  bool  $pendingOnly  If true, only pending reports are returned
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReportsForAdmin(bool $pendingOnly = true)
    {
        $query = AbuseReport::with(['reporter', 'reportable']);

        if ($pendingOnly) {
            $query->pending();
        }

        return $query->latest()->get();
    }

    /**
     * Resolve an abuse report (mark as resolved).
     *
     * @param  AbuseReport  $report
     * @return void
     */
    public function resolveReport(AbuseReport $report): void
    {
        $report->markAsResolved();
    }

    /**
     * Reopen a resolved abuse report (mark as pending).
     *
     * @param  AbuseReport  $report
     * @return void
     */
    public function reopenReport(AbuseReport $report): void
    {
        $report->markAsPending();
    }

    /**
     * Validate that a reporter is authenticated and verified.
     *
     * @param  User  $user
     * @return void
     * @throws \Exception If validation fails
     */
    public function validateReporter(User $user): void
    {
        if (!$user) {
            throw new \Exception('Reporter must be authenticated.');
        }

        if (!$user->email_verified_at) {
            throw new \Exception('Reporter must be a verified user.');
        }

        // Additional checks: user must not be deactivated
        if ($user->deactivated_at) {
            throw new \Exception('Reporter is deactivated.');
        }
    }

    /**
     * Validate that the reported item exists.
     *
     * @param  string  $type  Model class (e.g., Project::class)
     * @param  int  $id
     * @return void
     * @throws \Exception If item does not exist
     */
    public function validateReportedItem(string $type, int $id): void
    {
        if (!class_exists($type)) {
            throw new \Exception('Invalid reported item type.');
        }

        /** @var Model $model */
        $model = new $type;
        if (!$model->where('id', $id)->exists()) {
            throw new \Exception('Reported item does not exist.');
        }
    }

    /**
     * Count pending abuse reports.
     *
     * @return int
     */
    public function countPendingReports(): int
    {
        return AbuseReport::pending()->count();
    }

    /**
     * Delete an abuse report (soft delete).
     *
     * @param  AbuseReport  $report
     * @return void
     */
    public function deleteReport(AbuseReport $report): void
    {
        $report->delete();
    }

    /**
     * Restore a soft‑deleted abuse report.
     *
     * @param  int  $id
     * @return AbuseReport
     */
    public function restoreReport(int $id): AbuseReport
    {
        $report = AbuseReport::withTrashed()->findOrFail($id);
        $report->restore();
        return $report;
    }
}
