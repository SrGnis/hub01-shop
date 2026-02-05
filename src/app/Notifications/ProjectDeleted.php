<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProjectDeleted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The project ID.
     *
     * @var int
     */
    protected int $projectId;

    /**
     * The project name.
     *
     * @var string
     */
    protected string $projectName;

    /**
     * The project type.
     *
     * @var string
     */
    protected string $projectType;

    /**
     * The name of the user who deleted the project.
     *
     * @var string
     */
    protected string $deletedByUserName;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, ?User $deletedByUser)
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->projectType = $project->projectType;
        $this->deletedByUserName = $deletedByUser?->name ?? config('app.name');

        Log::info('ProjectDeleted notification created', [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'deleted_by_user_name' => $this->deletedByUserName,
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
            ->subject('Project Deleted: '.$this->projectName)
            ->greeting('Hello!')
            ->line($this->deletedByUserName.' has deleted the project "'.$this->projectName.'".')
            ->line('The project will be permanently deleted after 14 days. Until then, you can still see it in your profile page.')
            ->line('Thank you for using HUB01 Shop!');
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
            'project_type' => $this->projectType,
            'deleted_by_user_name' => $this->deletedByUserName,
            'type' => 'project_deleted',
        ];
    }
}
