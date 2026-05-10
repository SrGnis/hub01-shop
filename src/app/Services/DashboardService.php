<?php

namespace App\Services;

use App\Enums\CollectionSystemType;
use App\Models\CollectionEntry;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;

class DashboardService
{
    /**
     * @return array{name: string, profile_url: string, avatar_url: ?string}
     */
    public function getProfileForUser(User $user): array
    {
        return [
            'name' => $user->name,
            'profile_url' => route('user.profile', ['user' => $user]),
            'avatar_url' => $user->getAvatarUrl(),
        ];
    }

    public function getAggregateDownloadsForUser(User $user): int
    {
        $projectIds = $this->getActiveProjectIdsForUser($user);

        return (int) ProjectVersion::query()
            ->join('project_version_daily_download', 'project_version.id', '=', 'project_version_daily_download.project_version_id')
            ->whereIn('project_version.project_id', $projectIds)
            ->sum('project_version_daily_download.downloads');
    }

    public function getAggregateFavoritesForUser(User $user): int
    {
        return (int) CollectionEntry::query()
            ->join('collection', 'collection.uid', '=', 'collection_entry.collection_uid')
            ->join('project', 'project.id', '=', 'collection_entry.project_id')
            ->join('membership', function ($join) use ($user) {
                $join->on('membership.project_id', '=', 'project.id')
                    ->where('membership.user_id', '=', $user->id)
                    ->where('membership.status', '=', 'active');
            })
            ->whereNull('project.deleted_at')
            ->where('collection.system_type', CollectionSystemType::FAVORITES->value)
            ->count();
    }

    /**
     * @return array<int, array{id: string, title: string, body: string, created_at_human: string, read: bool}>
     */
    public function getRecentNotificationsForUser(User $user): array
    {
        return $user->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                $data = is_array($notification->data) ? $notification->data : [];

                return [
                    'id' => (string) $notification->id,
                    'title' => (string) ($data['subject'] ?? class_basename($notification->type)),
                    'body' => (string) ($data['message'] ?? $data['body'] ?? ''),
                    'created_at_human' => $notification->created_at?->diffForHumans() ?? '',
                    'read' => $notification->read_at !== null,
                ];
            })
            ->values()
            ->all();
    }

    private function getActiveProjectIdsForUser(User $user)
    {
        return Project::withoutGlobalScopes()
            ->whereNull('project.deleted_at')
            ->whereHas('memberships', function ($query) use ($user) {
                $query->where('membership.user_id', $user->id)
                    ->where('membership.status', 'active');
            })
            ->pluck('project.id');
    }
}

