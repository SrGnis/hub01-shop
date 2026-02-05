<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserDeactivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Account Has Been Deactivated - HUB01 Shop')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your HUB01 Shop account has been deactivated by an administrator.')
            ->line('What this means:')
            ->line('• You will no longer be able to log in to your account')
            ->line('• Your projects and data remain intact')
            ->line('• If you believe this was done in error, please contact our support team')
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
            'type' => 'user_deactivated',
        ];
    }
}

