<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Project Quotas Configuration
    |--------------------------------------------------------------------------
    |
    | Define default quota limits for projects, versions, and storage.
    | These values can be overridden at the project type level or project level.
    | Admins are exempt from all quota limits.
    |
    */

    'pending_projects_max' => env('QUOTA_PENDING_PROJECTS_MAX', 3),
    'total_storage_max' => env('QUOTA_TOTAL_STORAGE_MAX', 1073741824), // 1GB in bytes
    'project_storage_max' => env('QUOTA_PROJECT_STORAGE_MAX', 524288000), // 500MB in bytes

    'versions_per_day_max' => env('QUOTA_VERSION_PER_DAY_MAX', 5),
    'version_size_max' => env('QUOTA_VERSION_SIZE_MAX', 104857600), // 100MB in bytes
    'files_per_version_max' => env('QUOTA_FILES_PER_VERSION_MAX', 5),
    'file_size_max' => env('QUOTA_FILE_SIZE_MAX', 104857600), // 100MB in bytes
];
