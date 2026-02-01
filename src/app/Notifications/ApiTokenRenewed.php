<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiTokenRenewed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $tokenName,
        public \DateTime $newExpirationDate
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
            ->subject('API Token Renewed - HUB01 Shop')
            ->greeting('Hello!')
            ->line('An API token has been renewed for your account.')
            ->line('**Token Name:** ' . $this->tokenName)
            ->line('**New Expiration Date:** ' . $this->newExpirationDate->format('Y-m-d H:i:s'))
            ->line('You can manage your API tokens in your account security settings.')
            ->line('If you did not renew this token, please contact our support team immediately.')
            ->salutation('Regards,' . PHP_EOL . config('app.name'));
    }
}
