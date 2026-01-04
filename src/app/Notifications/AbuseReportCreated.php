<?php

namespace App\Notifications;

use App\Models\AbuseReport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AbuseReportCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected AbuseReport $abuseReport;
    protected User $reporter;
    protected string $reportableType;
    protected string $reportableName;

    /**
     * Create a new notification instance.
     */
    public function __construct(AbuseReport $abuseReport, User $reporter)
    {
        $this->abuseReport = $abuseReport;
        $this->reporter = $reporter;

        // Get reportable item details
        $this->reportableType = class_basename($abuseReport->reportable_type);

        // Try to get the reportable item's name or identifier
        try {
            $reportable = $abuseReport->reportable;
            $this->reportableName = method_exists($reportable, 'getName')
                ? $reportable->getName()
                : ($reportable->name ?? $reportable->title ?? 'Unknown');
        } catch (\Exception $e) {
            $this->reportableName = 'Unknown';
        }

        Log::info('AbuseReportCreated notification created', [
            'report_id' => $abuseReport->id,
            'reporter_id' => $reporter->id,
            'reportable_type' => $this->reportableType,
            'reportable_name' => $this->reportableName,
        ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Abuse Report Submitted - HUB01 Shop')
            ->greeting('Hello Admin!')
            ->line('A new abuse report has been submitted that requires your attention.')
            ->line('**Report Details:**')
            ->line('- **Report ID:** ' . $this->abuseReport->id)
            ->line('- **Reported Item:** ' . $this->reportableName)
            ->line('- **Item Type:** ' . $this->reportableType)
            ->line('- **Reporter:** ' . $this->reporter->name)
            ->line('- **Reason:** ' . $this->abuseReport->reason)
            ->line('- **Submitted:** ' . $this->abuseReport->created_at->format('Y-m-d H:i:s'))
            ->action('Review Abuse Report', url('/admin/abuse-reports'))
            ->line('Please review this report as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->abuseReport->id,
            'reporter_id' => $this->reporter->id,
            'reporter_name' => $this->reporter->name,
            'reportable_type' => $this->reportableType,
            'reportable_name' => $this->reportableName,
            'reason' => $this->abuseReport->reason,
            'submitted_at' => $this->abuseReport->created_at->toISOString(),
            'type' => 'abuse_report_created',
        ];
    }
}
