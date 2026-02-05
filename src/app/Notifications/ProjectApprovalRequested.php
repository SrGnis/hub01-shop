<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProjectApprovalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $projectId;
    protected string $projectName;
    protected string $projectSlug;
    protected string $projectType;
    protected string $ownerName;
    protected ?string $ownerEmail;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, ?User $owner)
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->projectSlug = $project->slug;
        $this->projectType = $project->projectType->display_name;
        $this->ownerName = $owner?->name ?? 'Unknown';
        $this->ownerEmail = $owner?->email;

        Log::info('ProjectApprovalRequested notification created', [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'owner_name' => $this->ownerName,
        ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Project Approval Requested: ' . $this->projectName)
            ->greeting('Hello Admin!')
            ->line('A new project "' . $this->projectName . '" by ' . $this->ownerName . ' is pending your review.')
            ->line('Project Type: ' . $this->projectType)
            ->action('Review Project', url('/admin/projects/approvals'))
            ->line('Please review and approve or reject this project.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'project_slug' => $this->projectSlug,
            'project_type' => $this->projectType,
            'owner_name' => $this->ownerName,
            'owner_email' => $this->ownerEmail,
            'type' => 'project_approval_requested',
        ];
    }
}
