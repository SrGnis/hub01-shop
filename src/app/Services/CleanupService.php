<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Notifications\UnverifiedUserDeletionWarning;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupService
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * List all orphaned files in storage that are not referenced in the database
     *
     * @return Collection Collection of orphaned file paths with metadata
     */
    public function listOrphanedFiles(): Collection
    {
        $searchLocations = config('cleanup.storage');
        $orphanedFiles = collect();
        $referencedFiles = $this->getReferencedFiles();
        logger()->debug('Referenced files count', ['count' => $referencedFiles->count()]);

        logger()->debug('Starting orphaned files scan', ['search_locations' => $searchLocations]);

        foreach ($searchLocations as $location) {
            $disk = $location['disk'];
            $path = $location['path'];

            logger()->debug('Scanning disk and path', ['disk' => $disk, 'path' => $path]);
            try {
                $files = Storage::disk($disk)->allFiles($path);
                logger()->debug('Files found in path', ['disk' => $disk, 'path' => $path, 'count' => count($files)]);

                foreach ($files as $file) {
                    $fullPath = $file;

                    if (! $referencedFiles->contains($fullPath)) {
                        logger()->debug('Found orphaned file', ['disk' => $disk, 'path' => $file]);
                        $orphanedFiles->push([
                            'disk' => $disk,
                            'path' => $file,
                            'size' => Storage::disk($disk)->size($file),
                            'last_modified' => Storage::disk($disk)->lastModified($file),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to scan storage path', [
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        logger()->debug('Orphaned files scan completed', ['total_orphaned' => $orphanedFiles->count()]);

        return $orphanedFiles;
    }

    /**
     * Delete all orphaned files from storage
     *
     * @return int Number of files deleted
     */
    public function deleteOrphanedFiles(): int
    {
        $orphanedFiles = $this->listOrphanedFiles();
        $deletedCount = 0;

        foreach ($orphanedFiles as $fileInfo) {
            try {
                if (Storage::disk($fileInfo['disk'])->delete($fileInfo['path'])) {
                    $deletedCount++;
                    Log::info('Deleted orphaned file', [
                        'disk' => $fileInfo['disk'],
                        'path' => $fileInfo['path'],
                        'size' => $fileInfo['size'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to delete orphaned file', [
                    'disk' => $fileInfo['disk'],
                    'path' => $fileInfo['path'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Orphaned files cleanup completed', [
            'files_found' => $orphanedFiles->count(),
            'files_deleted' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Get all file paths referenced in the database
     *
     * @return Collection Collection of referenced file paths
     */
    private function getReferencedFiles(): Collection
    {
        $referencedFiles = collect();

        // Get project files (stored in local disk under 'projects' path)
        // Note: ProjectFile doesn't use SoftDeletes, so no withTrashed() needed
        $projectFiles = ProjectFile::whereNotNull('path')
            ->pluck('path');
        $referencedFiles = $referencedFiles->merge($projectFiles);

        // Get user avatars (stored in public disk)
        $avatars = User::withTrashed()
            ->whereNotNull('avatar')
            ->pluck('avatar');
        $referencedFiles = $referencedFiles->merge($avatars);

        // Get project logos (stored in public disk)
        $logos = Project::withTrashed()
            ->whereNotNull('logo_path')
            ->pluck('logo_path');
        $referencedFiles = $referencedFiles->merge($logos);

        return $referencedFiles->unique()->filter();
    }

    /**
     * List unverified users, optionally filtered by age
     *
     * @param  int|null  $daysOld  Minimum age in days (null for all unverified users)
     * @return Collection Collection of unverified users
     */
    public function listUnverifiedUsers(?int $daysOld = null): Collection
    {
        $query = User::whereNull('email_verified_at');

        if ($daysOld !== null) {
            $cutoffDate = now()->subDays($daysOld);
            $query->where('created_at', '<=', $cutoffDate);
        }

        return $query->get();
    }

    /**
     * Send deletion warning emails to unverified users past the warning threshold
     *
     * @return int Number of warnings sent
     */
    public function sendDeletionWarnings(): int
    {
        $warningThreshold = config('cleanup.unverified_users.warning_threshold_days');
        $deletionThreshold = config('cleanup.unverified_users.deletion_threshold_days');

        $users = User::whereNull('email_verified_at')
            ->whereNull('unverified_deletion_warning_sent_at')
            ->where('created_at', '<=', now()->subDays($warningThreshold))
            ->get();

        $warningsSent = 0;

        foreach ($users as $user) {
            try {
                $daysUntilDeletion = $deletionThreshold - $warningThreshold;
                $user->notify(new UnverifiedUserDeletionWarning($daysUntilDeletion));

                $user->update(['unverified_deletion_warning_sent_at' => now()]);
                $warningsSent++;

                Log::info('Sent unverified user deletion warning', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'days_until_deletion' => $daysUntilDeletion,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send deletion warning', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Deletion warnings sent', [
            'warnings_sent' => $warningsSent,
            'users_checked' => $users->count(),
        ]);

        return $warningsSent;
    }

    /**
     * Delete unverified users past the deletion threshold
     *
     * @return int Number of users deleted
     */
    public function deleteUnverifiedUsers(): int
    {
        $deletionThreshold = config('cleanup.unverified_users.deletion_threshold_days');

        $users = User::whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays($deletionThreshold))
            ->get();

        $deletedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($users as $user) {
                try {
                    $this->userService->delete($user);
                    $deletedCount++;

                    Log::info('Deleted unverified user', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'days_since_registration' => now()->diffInDays($user->created_at),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to delete unverified user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Unverified users cleanup completed', [
                'users_found' => $users->count(),
                'users_deleted' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unverified users cleanup failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $deletedCount;
    }
}
