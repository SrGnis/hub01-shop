<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProjectRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $projectId;
    protected string $projectName;
    protected string $projectSlug;
    protected string $projectType;
    protected string $adminName;
    protected string $rejectionReason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, User $admin, string $rejectionReason)
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->projectSlug = $project->slug;
        $this->projectType = $project->projectType->display_name;
        $this->adminName = $admin->name;
        $this->rejectionReason = $rejectionReason;

        Log::info('ProjectRejected notification created', [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'admin_name' => $this->adminName,
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
        $projectEditUrl = route('project.edit', [
            'projectType' => $this->projectType,
            'project' => $this->projectSlug,
        ]);

        return (new MailMessage)
            ->subject('Project Rejected: ' . $this->projectName)
            ->greeting('Hello!')
            ->line('Your project "' . $this->projectName . '" has been rejected by ' . $this->adminName . '.')
            ->line('**Rejection Reason:**')
            ->line($this->rejectionReason)
            ->line('You can edit your project to address the concerns and resubmit it for review.')
            ->action('Edit Project', $projectEditUrl)
            ->line('Thank you for your understanding!');
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
            'admin_name' => $this->adminName,
            'rejection_reason' => $this->rejectionReason,
            'type' => 'project_rejected',
        ];
    }
}
