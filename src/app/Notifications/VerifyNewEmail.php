<?php

namespace App\Notifications;

use App\Models\PendingEmailChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyNewEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private PendingEmailChange $pendingChange
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'email-change.verify',
            $this->pendingChange->verification_expires_at,
            ['token' => $this->pendingChange->verification_token]
        );

        return (new MailMessage)
            ->subject('Verify Your New Email Address - HUB01 Shop')
            ->greeting('Hello!')
            ->line('Your email change has been authorized. To complete the process, please verify your new email address by clicking the button below.')
            ->line('This link will expire in 24 hours.')
            ->action('Verify New Email', $verificationUrl)
            ->line('If you did not request this change, please contact our support team immediately.')
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
            'new_email' => $this->pendingChange->new_email,
            'type' => 'email_verify_new',
        ];
    }
}

