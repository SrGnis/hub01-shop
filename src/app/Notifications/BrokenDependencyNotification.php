<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class BrokenDependencyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $dependentProjectSlug;

    protected string $dependentProjectName;

    protected string $dependentProjectType;

    protected ?int $dependentVersionId;

    protected ?string $dependentVersionNumber;

    protected string $deletedProjectSlug;

    protected string $deletedProjectName;

    protected ?string $deletedVersionNumber;

    protected string $deletedByUserName;

    protected bool $isProjectDeleted;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        Project $dependentProject,
        ?ProjectVersion $dependentVersion,
        string $deletedProjectSlug,
        string $deletedProjectName,
        ?string $deletedVersionNumber,
        ?User $deletedByUser,
        bool $isProjectDeleted = false
    ) {
        $this->dependentProjectSlug = $dependentProject->slug;
        $this->dependentProjectName = $dependentProject->name;
        $this->dependentProjectType = $dependentProject->projectType;

        if ($dependentVersion) {
            $this->dependentVersionId = $dependentVersion->id;
            $this->dependentVersionNumber = $dependentVersion->version;
        } else {
            $this->dependentVersionId = null;
            $this->dependentVersionNumber = null;
        }

        $this->deletedProjectSlug = $deletedProjectSlug;
        $this->deletedProjectName = $deletedProjectName;
        $this->deletedVersionNumber = $deletedVersionNumber;
        $this->deletedByUserName = $deletedByUser?->name ?? config('app.name');
        $this->isProjectDeleted = $isProjectDeleted;

        Log::info('BrokenDependencyNotification created', [
            'dependent_project_slug' => $this->dependentProjectSlug,
            'dependent_project_name' => $this->dependentProjectName,
            'dependent_version_id' => $this->dependentVersionId,
            'dependent_version_number' => $this->dependentVersionNumber,
            'deleted_project_slug' => $this->deletedProjectSlug,
            'deleted_project_name' => $this->deletedProjectName,
            'deleted_version_number' => $this->deletedVersionNumber,
            'deleted_by_user_name' => $this->deletedByUserName,
            'is_project_deleted' => $this->isProjectDeleted,
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
        $url = route('project.version.edit', [
            'projectType' => $this->dependentProjectType,
            'project' => $this->dependentProjectSlug,
            'version_key' => $this->dependentVersionNumber,
        ]);

        $subject = $this->isProjectDeleted
            ? 'Dependency Broken: Project Deleted'
            : 'Dependency Broken: Version Deleted';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello!');

        if ($this->isProjectDeleted) {
            $message->line($this->deletedByUserName.' has deleted the project "'.$this->deletedProjectName.'".');
            $message->line('This project was a dependency for your project "'.$this->dependentProjectName.'"'.
                ($this->dependentVersionNumber ? ' version '.$this->dependentVersionNumber : '').'.');
        } else {
            $message->line($this->deletedByUserName.' has deleted version '.$this->deletedVersionNumber.
                ' of the project "'.$this->deletedProjectName.'".');
            $message->line('This version was a dependency for your project "'.$this->dependentProjectName.'"'.
                ($this->dependentVersionNumber ? ' version '.$this->dependentVersionNumber : '').'.');
        }

        if ($this->dependentVersionNumber) {
            $message->line('You may need to update your project version to fix this broken dependency.');
            $message->action('Edit Project Version', $url);
        } else {
            $message->line('You may need to update your project dependencies to fix this issue.');
        }

        $message->line('Thank you for using HUB01 Shop!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'dependent_project_slug' => $this->dependentProjectSlug,
            'dependent_project_name' => $this->dependentProjectName,
            'dependent_project_type' => $this->dependentProjectType,
            'dependent_version_id' => $this->dependentVersionId,
            'dependent_version_number' => $this->dependentVersionNumber,
            'deleted_project_slug' => $this->deletedProjectSlug,
            'deleted_project_name' => $this->deletedProjectName,
            'deleted_version_number' => $this->deletedVersionNumber,
            'deleted_by_user_name' => $this->deletedByUserName,
            'is_project_deleted' => $this->isProjectDeleted,
            'type' => 'broken_dependency',
        ];
    }
}
