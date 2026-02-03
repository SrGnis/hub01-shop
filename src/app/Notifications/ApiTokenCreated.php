<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiTokenCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $tokenName,
        public ?\DateTime $expirationDate = null
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail','database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $expirationText = $this->expirationDate
            ? $this->expirationDate->format('Y-m-d H:i:s')
            : 'Never';

        return (new MailMessage)
            ->subject('API Token Created - HUB01 Shop')
            ->greeting('Hello!')
            ->line('A new API token has been created for your account.')
            ->line('**Token Name:** ' . $this->tokenName)
            ->line('**Expiration Date:** ' . $expirationText)
            ->line('You can manage your API tokens in your account security settings.')
            ->line('If you did not create this token, please contact our support team immediately.')
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
            'token_name' => $this->tokenName,
            'expiration_date' => $this->expirationDate?->format('Y-m-d H:i:s'),
            'type' => 'api_token_created',
        ];
    }
}
