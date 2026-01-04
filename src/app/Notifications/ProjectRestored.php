<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProjectRestored extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The project slug.
     *
     * @var string
     */
    protected string $projectSlug;

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
     * The name of the user who restored the project.
     *
     * @var string
     */
    protected ?string $restoredByUserName;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, ?User $restoredByUser)
    {
        $this->projectSlug = $project->slug;
        $this->projectName = $project->name;
        $this->projectType = $project->projectType->value;
        $this->restoredByUserName = $restoredByUser?->name ?? config('app.name');

        Log::info('ProjectRestored notification created', [
            'project_' => $this->projectSlug,
            'project_name' => $this->projectName,
            'project_type' => $this->projectType,
            'restored_by_user_name' => $this->restoredByUserName,
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
        $url = route('project.show', ['projectType' => $this->projectType, 'project' => $this->projectSlug]);

        return (new MailMessage)
            ->subject('Project Restored: '.$this->projectName)
            ->greeting('Hello!')
            ->line($this->restoredByUserName.' has restored the project "'.$this->projectName.'".')
            ->line('The project is now active again and all its data has been restored.')
            ->action('View Project', $url)
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
            'project_id' => $this->projectSlug,
            'project_name' => $this->projectName,
            'project_type' => $this->projectType,
            'restored_by_user_name' => $this->restoredByUserName,
            'type' => 'project_restored',
        ];
    }
}
