<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserReactivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = route('login');

        return (new MailMessage)
            ->subject('Your Account Has Been Reactivated - HUB01 Shop')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your HUB01 Shop account has been reactivated by an administrator.')
            ->line('You can now log in to your account and access all features as before.')
            ->action('Log In Now', $loginUrl)
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
            'type' => 'user_reactivated',
        ];
    }
}

