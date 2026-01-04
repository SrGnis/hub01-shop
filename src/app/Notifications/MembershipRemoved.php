<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRemoved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The project ID.
     *
     * @var int
     */
    protected int $projectId;

    /**
     * The project name.
     *
     * @var string
     */
    protected string $projectName;

    /**
     * The removed user ID.
     *
     * @var int
     */
    protected int $removedUserId;

    /**
     * The removed user name.
     *
     * @var string
     */
    protected string $removedUserName;

    /**
     * The user who performed the removal name.
     *
     * @var string
     */
    protected string $removedByUserName;

    /**
     * Whether the user removed themselves.
     *
     * @var bool
     */
    protected bool $isSelfRemoval;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, User $removedUser, ?User $removedByUser, bool $isSelfRemoval = false)
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->removedUserId = $removedUser->id;
        $this->removedUserName = $removedUser->name;
        $this->removedByUserName = $removedByUser?->name ?? config('app.name');
        $this->isSelfRemoval = $isSelfRemoval;
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
        $url = route('project.show', ['projectType' => 'mod', 'project' => $this->projectId]);

        $subject = $this->isSelfRemoval
            ? $this->removedUserName.' has left the project: '.$this->projectName
            : 'Member removed from project: '.$this->projectName;

        $message = $this->isSelfRemoval
            ? $this->removedUserName.' has left the project "'.$this->projectName.'"'
            : $this->removedByUserName.' has removed '.$this->removedUserName.' from the project "'.$this->projectName.'"';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello!')
            ->line($message)
            ->line('As a member of this project, you are being notified of this change.')
            ->action('View Project', $url)
            ->line('Thank you for using HUB01 Shop!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'removed_user_id' => $this->removedUserId,
            'removed_user_name' => $this->removedUserName,
            'removed_by_user_name' => $this->removedByUserName,
            'is_self_removal' => $this->isSelfRemoval,
            'type' => 'membership_removed',
        ];
    }
}
