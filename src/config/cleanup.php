<?php

$projectsConfig = include(config_path('projects.php'));

return [

    /*
    |--------------------------------------------------------------------------
    | Unverified Users Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the automatic cleanup of unverified users.
    | Users who haven't verified their email will receive a warning email
    | after the warning threshold, and will be deleted after the deletion
    | threshold.
    |
    */

    'unverified_users' => [
        // Number of days after registration to send deletion warning
        'warning_threshold_days' => env('CLEANUP_UNVERIFIED_WARNING_DAYS', 7),

        // Number of days after registration to delete unverified users
        'deletion_threshold_days' => env('CLEANUP_UNVERIFIED_DELETION_DAYS', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the automatic cleanup of orphaned storage files.
    | Files that exist in storage but are not referenced in the database
    | will be identified and can be deleted.
    |
    */

    'storage' => [
        [
            'disk' => 'public',
            'path' => 'avatars'
        ],
        [
            'disk' => 'public',
            'path' => 'project-logos'
        ],
        [
            'disk' => data_get($projectsConfig, 'files-disk'),
            'path' => data_get($projectsConfig, 'files-directory')
        ],
    ],

];
