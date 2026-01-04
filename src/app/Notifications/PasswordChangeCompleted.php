<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangeCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password Changed Successfully - HUB01 Shop')
            ->greeting('Hello!')
            ->line('Your password has been successfully changed.')
            ->line('If you did not make this change, please contact our support team immediately and change your password.')
            ->salutation('Regards,' . PHP_EOL . config('app.name'));
    }
}

