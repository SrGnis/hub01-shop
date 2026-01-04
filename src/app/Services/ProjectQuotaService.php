<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectQuotaService
{
    /**
     * Get the number of pending projects for a user
     */
    public function getPendingProjectsCount(User $user): int
    {
        return Project::whereHas('owner', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('approval_status', ApprovalStatus::PENDING)
            ->count();
    }

    /**
     * Get the total storage used by all projects owned by a user (in bytes)
     */
    public function getTotalStorageUsed(User $user): int
    {
        $projects = Project::whereHas('owner', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->pluck('id');

        return (int) DB::table('project_file')
            ->join('project_version', 'project_file.project_version_id', '=', 'project_version.id')
            ->whereIn('project_version.project_id', $projects)
            ->sum('project_file.size');
    }

    /**
     * Get the storage used by a specific project (in bytes)
     */
    public function getProjectStorageUsed(Project $project): int
    {
        return (int) DB::table('project_file')
            ->join('project_version', 'project_file.project_version_id', '=', 'project_version.id')
            ->where('project_version.project_id', $project->id)
            ->sum('project_file.size');
    }

    /**
     * Check if a user can create a new project
     */
    public function canCreateProject(User $user): bool
    {
        if ($this->isExemptFromQuotas($user)) {
            return true;
        }

        $pendingCount = $this->getPendingProjectsCount($user);
        $limits = $this->getQuotaLimits($user);

        return $pendingCount < $limits['pending_projects_max'];
    }

    /**
     * Validate project creation and throw exception if quota exceeded
     *
     * @throws \Exception
     */
    public function validateProjectCreation(User $user): void
    {
        if ($this->isExemptFromQuotas($user)) {
            return;
        }

        $pendingCount = $this->getPendingProjectsCount($user);
        $limits = $this->getQuotaLimits($user);

        if ($pendingCount >= $limits['pending_projects_max']) {
            throw new \Exception(
                "You have reached the maximum number of pending projects ({$limits['pending_projects_max']}). " .
                "Please wait for your existing projects to be approved or rejected before creating new ones."
            );
        }
    }

    /**
     * Validate storage quota and throw exception if exceeded
     *
     * @throws \Exception
     */
    public function validateStorageQuota(User $user, int $additionalSize = 0): void
    {
        if ($this->isExemptFromQuotas($user)) {
            return;
        }

        $currentStorage = $this->getTotalStorageUsed($user);
        $limits = $this->getQuotaLimits($user);

        if (($currentStorage + $additionalSize) > $limits['total_storage_max']) {
            $maxGB = round($limits['total_storage_max'] / 1073741824, 2);
            $currentGB = round($currentStorage / 1073741824, 2);
            throw new \Exception(
                "Storage quota exceeded. You are using {$currentGB}GB of your {$maxGB}GB limit. " .
                "Please contact an admin to request a quota increase."
            );
        }
    }

    /**
     * Get applicable quota limits for a user
     * Priority: Project overrides > ProjectType overrides > Config defaults
     */
    public function getQuotaLimits(User $user, ?ProjectType $type = null, ?Project $project = null): array
    {
        // Start with config defaults
        $limits = [
            'pending_projects_max' => config('quotas.pending_projects_max'),
            'total_storage_max' => config('quotas.total_storage_max'),
            'project_storage_max' => config('quotas.project_storage_max'),
            'versions_per_day_max' => config('quotas.versions_per_day_max'),
            'version_size_max' => config('quotas.version_size_max'),
            'files_per_version_max' => config('quotas.files_per_version_max'),
            'file_size_max' => config('quotas.file_size_max'),
        ];

        // Apply project type overrides from database if available
        if ($type) {
            $type->load('quota');
            if ($type->quota) {
                $typeQuota = $type->quota->toArray();
                // Filter out null values and non-quota fields
                unset($typeQuota['id'], $typeQuota['project_type_id'], $typeQuota['created_at'], $typeQuota['updated_at']);
                $limits = array_merge($limits, array_filter($typeQuota, fn($val) => !is_null($val)));
            }
        }

        // Apply project-specific overrides from database if available (highest priority)
        if ($project) {
            $project->load('quota');
            if ($project->quota) {
                $projectQuota = $project->quota->toArray();
                // Filter out null values and non-quota fields
                unset($projectQuota['id'], $projectQuota['project_id'], $projectQuota['created_at'], $projectQuota['updated_at']);
                $limits = array_merge($limits, array_filter($projectQuota, fn($val) => !is_null($val)));
            }
        }

        // Apply user-specific overrides from database if available (highest priority)
        if ($user) {
            $user->load('quota');
            if ($user->quota) {
                $userQuota = $user->quota->toArray();
                // Filter out null values and non-quota fields
                unset($userQuota['id'], $userQuota['user_id'], $userQuota['created_at'], $userQuota['updated_at']);
                $limits = array_merge($limits, array_filter($userQuota, fn($val) => !is_null($val)));
            }
        }

        return $limits;
    }

    /**
     * Check if user is exempt from quotas (admins)
     */
    public function isExemptFromQuotas(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Get quota usage statistics for a user
     */
    public function getQuotaStatus(User $user): array
    {
        if ($this->isExemptFromQuotas($user)) {
            return [
                'exempt' => true,
                'pending_projects' => 0,
                'pending_projects_max' => null,
                'total_storage_used' => 0,
                'total_storage_max' => null,
            ];
        }

        $limits = $this->getQuotaLimits($user);

        return [
            'exempt' => false,
            'pending_projects' => $this->getPendingProjectsCount($user),
            'pending_projects_max' => $limits['pending_projects_max'],
            'total_storage_used' => $this->getTotalStorageUsed($user),
            'total_storage_max' => $limits['total_storage_max'],
            'total_storage_used_formatted' => $this->formatBytes($this->getTotalStorageUsed($user)),
            'total_storage_max_formatted' => $this->formatBytes($limits['total_storage_max']),
        ];
    }

    /**
     * Check if user has breached any quota
     */
    public function checkQuotaBreach(User $user): ?string
    {
        if ($this->isExemptFromQuotas($user)) {
            return null;
        }

        $limits = $this->getQuotaLimits($user);
        $pendingCount = $this->getPendingProjectsCount($user);
        $storageUsed = $this->getTotalStorageUsed($user);

        if ($pendingCount >= $limits['pending_projects_max']) {
            return "You have reached the maximum of {$limits['pending_projects_max']} pending projects.";
        }

        if ($storageUsed >= $limits['total_storage_max']) {
            $maxGB = round($limits['total_storage_max'] / 1073741824, 2);
            return "You have reached your storage limit of {$maxGB}GB.";
        }

        return null;
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }
}
