<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProjectPublished extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $projectId;

    protected string $projectName;

    protected string $projectSlug;

    protected string $projectType;

    public function __construct(Project $project)
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->projectSlug = $project->slug;
        $this->projectType = $project->projectType->value;

        Log::info('ProjectPublished notification created', [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projectUrl = route('project.show', [
            'projectType' => $this->projectType,
            'project' => $this->projectSlug,
        ]);

        return (new MailMessage)
            ->subject('Project Published: ' . $this->projectName)
            ->greeting('Hello!')
            ->line('The project "' . $this->projectName . '" has been published.')
            ->line('It is now visible to users and ready for releases.')
            ->action('View Project', $projectUrl)
            ->line('Thank you for contributing to ' . config('app.name') . '!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'project_slug' => $this->projectSlug,
            'project_type' => $this->projectType,
            'type' => 'project_published',
        ];
    }
}
