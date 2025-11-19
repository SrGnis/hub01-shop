<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $oldEmail,
        private string $newEmail
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Email Address Changed Successfully - HUB01 Shop')
            ->greeting('Hello!')
            ->line('Your email address has been successfully changed.')
            ->line('Old email: ' . $this->oldEmail)
            ->line('New email: ' . $this->newEmail)
            ->line('If you did not make this change, please contact our support team immediately.')
            ->salutation('Regards,' . PHP_EOL . config('app.name'));
    }
}

