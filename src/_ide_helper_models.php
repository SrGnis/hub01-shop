<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @mixin IdeHelperAbuseReport
 * @property int $id
 * @property string $reason
 * @property string $reportable_type
 * @property int $reportable_id
 * @property int $reporter_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $reportable
 * @property-read \App\Models\User $reporter
 * @method static \Database\Factories\AbuseReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport resolved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereReportableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereReportableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereReporterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbuseReport withoutTrashed()
 */
	class AbuseReport extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperMembership
 * @property int $id
 * @property string $role
 * @property bool $primary
 * @property string $status
 * @property int|null $invited_by
 * @property int $user_id
 * @property int $project_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $inviter
 * @property-read \App\Models\Project $mod
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership active()
 * @method static \Database\Factories\MembershipFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership wherePrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereUserId($value)
 */
	class Membership extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperPendingEmailChange
 * @property int $id
 * @property int $user_id
 * @property string $old_email
 * @property string $new_email
 * @property string $authorization_token
 * @property string|null $verification_token
 * @property string $status
 * @property \Illuminate\Support\Carbon $authorization_expires_at
 * @property \Illuminate\Support\Carbon|null $verification_expires_at
 * @property \Illuminate\Support\Carbon|null $authorized_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereAuthorizationExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereAuthorizationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereAuthorizedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereNewEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereOldEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereVerificationExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereVerificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingEmailChange whereVerifiedAt($value)
 */
	class PendingEmailChange extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperPendingPasswordChange
 * @property int $id
 * @property int $user_id
 * @property string $hashed_password
 * @property string $verification_token
 * @property string $status
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereHashedPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereVerificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingPasswordChange whereVerifiedAt($value)
 */
	class PendingPasswordChange extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProject
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $slug
 * @property string $summary
 * @property string $description
 * @property string|null $logo_path
 * @property string|null $website
 * @property string|null $issues
 * @property string|null $source
 * @property string $status
 * @property \App\Enums\ApprovalStatus $approval_status
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $deactivated_at
 * @property int $project_type_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Membership|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $active_users
 * @property-read int|null $active_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionDependency> $dependedOnBy
 * @property-read int|null $depended_on_by_count
 * @property-read mixed $downloads
 * @property-read string $formatted_size
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTag> $mainTags
 * @property-read int|null $main_tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Membership> $memberships
 * @property-read int|null $memberships_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $owner
 * @property-read int|null $owner_count
 * @property-read mixed $pretty_name
 * @property-read \App\Models\ProjectType $projectType
 * @property-read \App\Models\ProjectQuota|null $quota
 * @property-read mixed $recent_release_date
 * @property-read mixed $recent_versions
 * @property-read \App\Models\User|null $reviewedBy
 * @property-read mixed $size
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTag> $tags
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersion> $versions
 * @property-read int|null $versions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project accessScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project deactivated()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project draft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project exclude(array $columns)
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project globalSearchScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereApprovalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeactivatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIssues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProjectTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withRelations()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withStats()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 */
	class Project extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProjectFile
 * @property int $id
 * @property string $name
 * @property string $path
 * @property int $size
 * @property int $project_version_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $download_url
 * @property-read \App\Models\ProjectVersion $projectVersion
 * @property-read mixed $sha1
 * @method static \Database\Factories\ProjectFileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile whereProjectVersionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectFile whereUpdatedAt($value)
 */
	class ProjectFile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property int|null $project_storage_max
 * @property int|null $versions_per_day_max
 * @property int|null $version_size_max
 * @property int|null $files_per_version_max
 * @property int|null $file_size_max
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project $project
 * @method static \Database\Factories\ProjectQuotaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereFileSizeMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereFilesPerVersionMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereProjectStorageMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereVersionSizeMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectQuota whereVersionsPerDayMax($value)
 */
	class ProjectQuota extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProjectTag
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $icon
 * @property int|null $project_tag_group_id
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectTag> $children
 * @property-read int|null $children_count
 * @property-read mixed $has_sub_tags
 * @property-read ProjectTag|null $mainTag
 * @property-read ProjectTag|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectType> $projectTypes
 * @property-read int|null $project_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectTag> $subTags
 * @property-read int|null $sub_tags_count
 * @property-read \App\Models\ProjectTagGroup|null $tagGroup
 * @method static \Database\Factories\ProjectTagFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag onlyMain()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag onlySub()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereProjectTagGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereUpdatedAt($value)
 */
	class ProjectTag extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProjectTagGroup
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectType> $projectTypes
 * @property-read int|null $project_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTag> $tags
 * @property-read int|null $tags_count
 * @method static \Database\Factories\ProjectTagGroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup whereUpdatedAt($value)
 */
	class ProjectTagGroup extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProjectType
 * @property int $id
 * @property string $value
 * @property string $display_name
 * @property string $icon
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTagGroup> $projectTagGroups
 * @property-read int|null $project_tag_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTag> $projectTags
 * @property-read int|null $project_tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionTagGroup> $projectVersionTagGroups
 * @property-read int|null $project_version_tag_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionTag> $projectVersionTags
 * @property-read int|null $project_version_tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \App\Models\ProjectTypeQuota|null $quota
 * @method static \Database\Factories\ProjectTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectType whereValue($value)
 */
	class ProjectType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_type_id
 * @property int|null $project_storage_max
 * @property int|null $versions_per_day_max
 * @property int|null $version_size_max
 * @property int|null $files_per_version_max
 * @property int|null $file_size_max
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProjectType $projectType
 * @method static \Database\Factories\ProjectTypeQuotaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereFileSizeMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereFilesPerVersionMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereProjectStorageMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereProjectTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereVersionSizeMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTypeQuota whereVersionsPerDayMax($value)
 */
	class ProjectTypeQuota extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProjectVersion
 * @property int $id
 * @property string $name
 * @property string $version
 * @property string|null $changelog
 * @property \App\Enums\ReleaseType $release_type
 * @property \Illuminate\Support\Carbon $release_date
 * @property int $downloads
 * @property int $project_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $bg_color_class
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionDependency> $dependedOnBy
 * @property-read int|null $depended_on_by_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionDependency> $dependencies
 * @property-read int|null $dependencies_count
 * @property-read mixed $display_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionDependency> $embeddedDependencies
 * @property-read int|null $embedded_dependencies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectFile> $files
 * @property-read int|null $files_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionTag> $mainTags
 * @property-read int|null $main_tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionDependency> $optionalDependencies
 * @property-read int|null $optional_dependencies_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionDependency> $requiredDependencies
 * @property-read int|null $required_dependencies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionTag> $tags
 * @property-read int|null $tags_count
 * @method static \Database\Factories\ProjectVersionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereChangelog($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereDownloads($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereReleaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereReleaseType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersion whereVersion($value)
 */
	class ProjectVersion extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProjectVersionDependency
 * @property int $id
 * @property int $project_version_id
 * @property int|null $dependency_project_version_id
 * @property int|null $dependency_project_id
 * @property \App\Enums\DependencyType $dependency_type
 * @property string|null $dependency_name
 * @property string|null $dependency_version
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $bg_color_class
 * @property-read \App\Models\Project|null $dependencyProject
 * @property-read \App\Models\ProjectVersion|null $dependencyProjectVersion
 * @property-read mixed $display_name
 * @property-read \App\Models\ProjectVersion $projectVersion
 * @method static \Database\Factories\ProjectVersionDependencyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereDependencyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereDependencyProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereDependencyProjectVersionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereDependencyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereDependencyVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereProjectVersionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionDependency whereUpdatedAt($value)
 */
	class ProjectVersionDependency extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProjectVersionTag
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $icon
 * @property int|null $project_version_tag_group_id
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectVersionTag> $children
 * @property-read int|null $children_count
 * @property-read bool $has_sub_tags
 * @property-read ProjectVersionTag|null $mainTag
 * @property-read ProjectVersionTag|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectType> $projectTypes
 * @property-read int|null $project_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersion> $projectVersions
 * @property-read int|null $project_versions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectVersionTag> $subTags
 * @property-read int|null $sub_tags_count
 * @property-read \App\Models\ProjectVersionTagGroup|null $tagGroup
 * @method static \Database\Factories\ProjectVersionTagFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag onlyMain()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag onlySub()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereProjectVersionTagGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereUpdatedAt($value)
 */
	class ProjectVersionTag extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperProjectVersionTagGroup
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectType> $projectTypes
 * @property-read int|null $project_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionTag> $tags
 * @property-read int|null $tags_count
 * @method static \Database\Factories\ProjectVersionTagGroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup whereUpdatedAt($value)
 */
	class ProjectVersionTagGroup extends \Eloquent {}
}

namespace App\Models{
/**
 * @mixin IdeHelperUser
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $unverified_deletion_warning_sent_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $bio
 * @property string $role
 * @property string|null $avatar
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $deactivated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Membership> $activeMemberships
 * @property-read int|null $active_memberships_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Membership> $memberships
 * @property-read int|null $memberships_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Membership|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $ownedProjects
 * @property-read int|null $owned_projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PendingEmailChange> $pendingEmailChanges
 * @property-read int|null $pending_email_changes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Membership> $pendingInvitations
 * @property-read int|null $pending_invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PendingPasswordChange> $pendingPasswordChanges
 * @property-read int|null $pending_password_changes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \App\Models\UserQuota|null $quota
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $reviewedProjects
 * @property-read int|null $reviewed_projects_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User searchScope($term)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeactivatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUnverifiedDeletionWarningSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\MustVerifyEmail {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $total_storage_max
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\UserQuotaFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserQuota newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserQuota newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserQuota query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserQuota whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserQuota whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserQuota whereTotalStorageMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserQuota whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserQuota whereUserId($value)
 */
	class UserQuota extends \Eloquent {}
}

