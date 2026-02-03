<?php

namespace App\Notifications;

use App\Models\PendingPasswordChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConfirmPasswordChange extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private PendingPasswordChange $pendingChange
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'password-change.verify',
            $this->pendingChange->expires_at,
            ['token' => $this->pendingChange->verification_token]
        );

        return (new MailMessage)
            ->subject('Confirm Your Password Change - HUB01 Shop')
            ->greeting('Hello!')
            ->line('A password change has been requested for your account.')
            ->line('To confirm this change, please click on button below.')
            ->line('This link will expire in 1 hour.')
            ->action('Confirm Password Change', $verificationUrl)
            ->line('If you did not request this change, change your password immediately.')
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
            'type' => 'password_change_confirm',
        ];
    }
}
