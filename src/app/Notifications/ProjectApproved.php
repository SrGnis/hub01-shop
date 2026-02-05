<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProjectApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $projectId;
    protected string $projectName;
    protected string $projectSlug;
    protected string $projectType;
    protected string $adminName;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, User $admin)
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->projectSlug = $project->slug;
        $this->projectType = $project->projectType->display_name;
        $this->adminName = $admin->name;

        Log::info('ProjectApproved notification created', [
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $projectUrl = route('project.show', [
            'projectType' => $this->projectType,
            'project' => $this->projectSlug,
        ]);

        return (new MailMessage)
            ->subject('Project Approved: ' . $this->projectName)
            ->greeting('Congratulations!')
            ->line('Your project "' . $this->projectName . '" has been approved by ' . $this->adminName . '.')
            ->line('Your project is now visible to all users and you can start uploading versions.')
            ->action('View Project', $projectUrl)
            ->line('Thank you for contributing to ' . config('app.name') . '!');
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
            'type' => 'project_approved',
        ];
    }
}
