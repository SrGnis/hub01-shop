<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiTokenRevoked extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $tokenName
    ) {}

    /**
     * Get the notification's delivery channels.
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
            ->subject('API Token Revoked - HUB01 Shop')
            ->greeting('Hello!')
            ->line('An API token has been revoked for your account.')
            ->line('**Token Name:** ' . $this->tokenName)
            ->line('This token can no longer be used to access the API.')
            ->line('If you did not revoke this token, please contact our support team immediately.')
            ->salutation('Regards,' . PHP_EOL . config('app.name'));
    }
}
