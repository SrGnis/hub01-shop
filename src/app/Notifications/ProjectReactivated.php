<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectReactivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Project $project
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projectUrl = route('project.show', [
            'projectType' => $this->project->projectType,
            'project' => $this->project,
        ]);

        return (new MailMessage)
            ->subject('Your Project Has Been Reactivated - HUB01 Shop')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your project "' . $this->project->name . '" has been reactivated by an administrator.')
            ->line('Your project is now fully operational:')
            ->line('• The project is visible in search results again')
            ->line('• You can edit the project')
            ->line('• You can upload new versions')
            ->action('View Project', $projectUrl)
            ->salutation('Regards,' . PHP_EOL . config('app.name'));
    }
}

