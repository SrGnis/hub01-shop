<?php

namespace App\Notifications;

use App\Models\PendingEmailChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AuthorizeEmailChange extends Notification implements ShouldQueue
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
        $authorizationUrl = URL::temporarySignedRoute(
            'email-change.authorize',
            $this->pendingChange->authorization_expires_at,
            ['token' => $this->pendingChange->authorization_token]
        );

        return (new MailMessage)
            ->subject('Authorize Your Email Change - HUB01 Shop')
            ->greeting('Hello!')
            ->line('You requested to change your email address to: ' . $this->pendingChange->new_email)
            ->line('To authorize this change, please click the button below. This link will expire in 1 hour.')
            ->action('Authorize Email Change', $authorizationUrl)
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
            'new_email' => $this->pendingChange->new_email,
            'type' => 'email_change_authorize',
        ];
    }
}

