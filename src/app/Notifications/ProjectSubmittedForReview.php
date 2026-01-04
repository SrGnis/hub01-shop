<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProjectSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $projectId;
    protected string $projectName;
    protected string $projectSlug;
    protected string $projectType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->projectSlug = $project->slug;
        $this->projectType = $project->projectType->display_name;

        Log::info('ProjectSubmittedForReview notification created', [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
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
            ->subject('Project Submitted for Review: ' . $this->projectName)
            ->greeting('Hello!')
            ->line('Your project "' . $this->projectName . '" has been successfully submitted for review.')
            ->line('An admin will review your project soon. You will receive a notification once your project has been approved or if it requires changes.')
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
            'type' => 'project_submitted_for_review',
        ];
    }
}
