# Cache Documentation

This document outlines all cache entries used in the HUB01 Shop application.

## Cache Configuration

The application uses Redis as the default cache driver, configured in `config/cache.php`:

```php
'default' => env('CACHE_STORE', 'redis'),
```

Available cache stores include:
- `none` (null driver)
- `array`
- `database`
- `file`
- `memcached`
- `redis` (default)
- `dynamodb`
- `octane`

## Cache Keys and Usage

### Project Tag Groups

**Key Format:** `project_tag_groups_by_type_{projectType->value}`
**TTL:** 24 hours
**Location:** `app/Livewire/ProjectSearch.php`
**Purpose:** Caches project tag groups for a specific project type to improve performance in the search interface.
**Cache Invalidation:**
- Cleared in `ProjectTagGroup` model when a tag group is created, updated, or deleted
- Should also be invalidated when a project version is created or updated since the query includes related tags

### Project Version Tag Groups

**Key Format:** `project_version_tag_groups_by_type_{projectType->value}`
**TTL:** 24 hours
**Location:** `app/Livewire/ProjectSearch.php` and `app/Livewire/ProjectVersionForm.php`
**Purpose:** Caches project version tag groups for a specific project type to improve performance in search and form interfaces.
**Cache Invalidation:**
- Cleared in `ProjectVersionTagGroup` model when a tag group is created, updated, or deleted
- Should also be invalidated when a project version is created or updated since the query includes related tags

### Project Version Tags

**Key Format:** `project_version_tags_by_type_{projectType->value}`
**TTL:** 24 hours
**Location:** `app/Livewire/ProjectVersionForm.php`
**Purpose:** Caches project version tags for a specific project type to improve performance in the version form interface.
**Cache Invalidation:** Cleared in `ProjectVersionTag` model when a tag is created, updated, or deleted.

### Project Version Tags (Per Version)

**Key Format:** `project_version_tags_{projectVersion->id}`
**TTL:** 24 hours
**Location:** `app/Models/ProjectVersion.php`
**Purpose:** Caches tags for a specific project version.
**Cache Invalidation:** Cleared in `ProjectVersionTag` model when a tag is created, updated, or deleted that affects this version.

### Project Downloads

**Key Format:** `project_downloads_{project->id}`
**TTL:** 24 hours
**Location:** `app/Models/Project.php`
**Purpose:** Caches the total download count for a project (sum of all version downloads).
**Cache Invalidation:** Cleared when a project version's download count changes or when a version is deleted.

### Project Recent Versions

**Key Format:** `project_recent_versions_{project->id}_{limit}`
**TTL:** 24 hours
**Location:** `app/Models/Project.php`
**Purpose:** Caches recent versions of a project with a specified limit.
**Cache Invalidation:** Cleared when a project version is created, updated, or deleted. Clears cache for common limit values (3, 5, 10).

### Project Recent Release Date

**Key Format:** `project_recent_release_date_{project->id}`
**TTL:** 24 hours
**Location:** `app/Models/Project.php`
**Purpose:** Caches the most recent release date of any version in the project.
**Cache Invalidation:** Cleared when a project version's release date changes or when a version is created or deleted.

### Project Size

**Key Format:** `project_size_{project->id}`
**TTL:** 24 hours
**Location:** `app/Models/Project.php`
**Purpose:** Caches the total size of all files across all versions of a project.
**Cache Invalidation:** Cleared when files are added, updated, or deleted from any version of the project.

## Cache Invalidation Patterns

The application uses model events to automatically clear relevant cache entries when data changes:

1. **Model Created/Updated/Deleted Events**: Most models have `booted` methods that listen for model events and clear related cache entries.

2. **Cascading Cache Invalidation**: When a model is updated, it may clear cache for related models. For example, updating a project version clears the project's download count cache.

## Notes

- All cache entries use a standard TTL of 24 hours.
- Download increments in `FileDownloadController` do not use caching to ensure accurate download counts.
- The application uses eager loading with cache to optimize database queries.
