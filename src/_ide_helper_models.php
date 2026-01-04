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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMembership {}
}

namespace App\Models{
/**
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPendingEmailChange {}
}

namespace App\Models{
/**
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPendingPasswordChange {}
}

namespace App\Models{
/**
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
 * @property int $project_type_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Membership|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $active_users
 * @property-read int|null $active_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersionDependency> $dependedOnBy
 * @property-read int|null $depended_on_by_count
 * @property-read string $formatted_size
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Membership> $memberships
 * @property-read int|null $memberships_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $owner
 * @property-read int|null $owner_count
 * @property-read mixed $pretty_name
 * @property-read \App\Models\ProjectType $projectType
 * @property-read mixed $recent_versions
 * @property-read mixed $size
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTag> $tags
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersion> $versions
 * @property-read int|null $versions_count
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIssues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProjectTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProject {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $path
 * @property int $size
 * @property int $project_version_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProjectVersion $projectVersion
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectFile {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $icon
 * @property int|null $project_tag_group_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectType> $projectTypes
 * @property-read int|null $project_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \App\Models\ProjectTagGroup|null $tagGroup
 * @method static \Database\Factories\ProjectTagFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereProjectTagGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectTag {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTagGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectTagGroup {}
}

namespace App\Models{
/**
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectType {}
}

namespace App\Models{
/**
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectVersion {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_version_id
 * @property int|null $dependency_project_version_id
 * @property int|null $dependency_project_id
 * @property string $dependency_type
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectVersionDependency {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $icon
 * @property int|null $project_version_tag_group_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectType> $projectTypes
 * @property-read int|null $project_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVersion> $projectVersions
 * @property-read int|null $project_versions_count
 * @property-read \App\Models\ProjectVersionTagGroup|null $tagGroup
 * @method static \Database\Factories\ProjectVersionTagFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereProjectVersionTagGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectVersionTag {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVersionTagGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectVersionTagGroup {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $bio
 * @property string $role
 * @property string|null $avatar
 * @property string|null $remember_token
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
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

