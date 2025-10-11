<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrimaryStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The project ID.
     *
     * @var int
     */
    protected $projectId;

    /**
     * The project name.
     *
     * @var string
     */
    protected $projectName;

    /**
     * The new primary user ID.
     *
     * @var int
     */
    protected $newPrimaryUserId;

    /**
     * The new primary user name.
     *
     * @var string
     */
    protected $newPrimaryUserName;

    /**
     * The user who made the change.
     *
     * @var int
     */
    protected $changedByUserId;

    /**
     * The user who made the change name.
     *
     * @var string
     */
    protected $changedByUserName;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, User $newPrimaryUser, User $changedByUser)
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->newPrimaryUserId = $newPrimaryUser->id;
        $this->newPrimaryUserName = $newPrimaryUser->name;
        $this->changedByUserId = $changedByUser->id;
        $this->changedByUserName = $changedByUser->name;
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

        return (new MailMessage)
            ->subject('Primary Owner Changed for Project: '.$this->projectName)
            ->greeting('Hello!')
            ->line($this->changedByUserName.' has changed the primary owner of the project "'.$this->projectName.'" to '.$this->newPrimaryUserName.'.')
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
            'new_primary_user_id' => $this->newPrimaryUserId,
            'new_primary_user_name' => $this->newPrimaryUserName,
            'changed_by_user_id' => $this->changedByUserId,
            'changed_by_user_name' => $this->changedByUserName,
            'type' => 'primary_status_changed',
        ];
    }
}
