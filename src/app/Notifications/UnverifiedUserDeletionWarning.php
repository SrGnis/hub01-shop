<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnverifiedUserDeletionWarning extends Notification
{
    use Queueable;

    /**
     * The number of days until deletion.
     */
    protected int $daysUntilDeletion;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $daysUntilDeletion)
    {
        $this->daysUntilDeletion = $daysUntilDeletion;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $deletionDate = now()->addDays($this->daysUntilDeletion)->format('F j, Y');

        return (new MailMessage)
            ->subject('Your account will be deleted soon')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your account was created on '.$notifiable->created_at->format('F j, Y').', but your email has not been verified.')
            ->line('To keep your account active, please verify your email address by clicking the button below.')
            ->action('Verify Email', url('/email/verify'))
            ->line('If you do not verify your email by **'.$deletionDate.'**, your account will be permanently deleted.')
            ->line('If you did not create an account, you can safely ignore this email.')
            ->salutation('Regards, '.config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'days_until_deletion' => $this->daysUntilDeletion,
            'deletion_date' => now()->addDays($this->daysUntilDeletion)->toISOString(),
        ];
    }
}
