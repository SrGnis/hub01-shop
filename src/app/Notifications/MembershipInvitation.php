<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class MembershipInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The membership ID.
     *
     * @var int
     */
    protected int $membership_id;

    /**
     * The project ID.
     *
     * @var int
     */
    protected int $project_id;

    /**
     * Additional data to store with the notification.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Create a new notification instance.
     */
    public function __construct(Membership $membership)
    {
        Log::info('Creating new MembershipInvitation notification', [
            'membership_id' => $membership->id,
            'inviter_id' => $membership->invited_by,
            'project_id' => $membership->project_id,
        ]);

        $this->membership_id = $membership->id;
        $this->project_id = $membership->project_id;

        $this->data = [
            'membership_role' => $membership->role,
            'project_name' => $membership->project->name,
            'project_summary' => $membership->project->summary,
            'inviter_name' => $membership->inviter?->name ?? config('app.name'),
        ];

        Log::info('MembershipInvitation notification created', [
            'membership_id' => $this->membership_id,
            'data' => $this->data,
        ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $membership = Membership::find($this->membership_id);

        $acceptUrl = URL::signedRoute('membership.accept', ['membership' => $membership->id]);
        // TODO: Add reject URL
        $rejectUrl = URL::signedRoute('membership.reject', ['membership' => $membership->id]);

        return (new MailMessage)
            ->subject('Invitation to join project: '.$this->data['project_name'])
            ->greeting('Hello!')
            ->line($this->data['inviter_name'].' has invited you to join the project "'.$this->data['project_name'].'" as a '.$this->data['membership_role'].'.')
            ->line('Project summary: '.$this->data['project_summary'])
            ->action('Accept Invitation', $acceptUrl)
            ->line('This invitation will expire in 7 days.')
            ->salutation('Thank you for using HUB01 Shop!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'membership_id' => $this->membership_id,
            'project_id' => $this->project_id,
            'project_name' => $this->data['project_name'],
            'inviter_name' => $this->data['inviter_name'],
            'role' => $this->data['membership_role'],
            'type' => 'membership_invitation',
        ];
    }
}
