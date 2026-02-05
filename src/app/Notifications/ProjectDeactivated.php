<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectDeactivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Project $project
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Project Has Been Deactivated - HUB01 Shop')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your project "' . $this->project->name . '" has been deactivated by an administrator.')
            ->line('What this means:')
            ->line('• The project will no longer appear in search results')
            ->line('• The project cannot be edited while deactivated')
            ->line('• New versions cannot be uploaded')
            ->line('• Existing downloads and data remain intact')
            ->line('If you believe this was done in error, please contact our support team.')
            ->salutation('Regards,' . PHP_EOL . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'type' => 'project_deactivated',
        ];
    }
}

