<?php

namespace Tests\Unit;

use App\Enums\CollectionSystemType;
use App\Enums\CollectionVisibility;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDailyDownload;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function profile_mapper_returns_expected_fields(): void
    {
        $service = new DashboardService();
        $user = User::factory()->create(['name' => 'Dash User']);

        $profile = $service->getProfileForUser($user);

        $this->assertSame('Dash User', $profile['name']);
        $this->assertStringContainsString(route('user.profile', ['user' => $user], false), $profile['profile_url']);
        $this->assertArrayHasKey('avatar_url', $profile);
    }

    #[Test]
    public function aggregates_downloads_and_favorites_with_active_membership_scope_and_deleted_guard(): void
    {
        $service = new DashboardService();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $activeProject = Project::factory()->create();
        $deletedProject = Project::factory()->create(['deleted_at' => now()]);

        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $activeProject->id, 'status' => 'active']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $deletedProject->id, 'status' => 'active']);

        $activeVersion = ProjectVersion::factory()->withoutDailyDownloads()->create(['project_id' => $activeProject->id]);

        ProjectVersionDailyDownload::factory()->forVersion($activeVersion)->withDownloads(40)->create();

        $favorites = \App\Models\Collection::create([
            'user_id' => $other->id,
            'name' => 'Fav',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        \App\Models\CollectionEntry::create(['collection_uid' => $favorites->uid, 'project_id' => $activeProject->id, 'sort_order' => 0]);
        \App\Models\CollectionEntry::create(['collection_uid' => $favorites->uid, 'project_id' => $deletedProject->id, 'sort_order' => 1]);

        $this->assertSame(40, $service->getAggregateDownloadsForUser($user));
        $this->assertSame(1, $service->getAggregateFavoritesForUser($user));
    }

    #[Test]
    public function recent_notifications_limit_and_fallbacks_are_applied(): void
    {
        Carbon::setTestNow('2026-05-10 12:00:00');
        $service = new DashboardService();
        $user = User::factory()->create();

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\FallbackNotification',
            'data' => ['message' => 'Msg fallback'],
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\BodyNotification',
            'data' => ['body' => 'Body fallback'],
            'read_at' => now(),
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\X' . $i,
                'data' => ['subject' => 'S' . $i, 'message' => 'B' . $i],
                'read_at' => null,
                'created_at' => now()->subMinutes($i + 2),
                'updated_at' => now()->subMinutes($i + 2),
            ]);
        }

        $items = $service->getRecentNotificationsForUser($user);
        $this->assertCount(5, $items);
        $this->assertTrue(collect($items)->contains(fn (array $n): bool => $n['title'] === 'FallbackNotification'));
        $this->assertTrue(collect($items)->contains(fn (array $n): bool => $n['body'] === 'Msg fallback'));
        $this->assertTrue(collect($items)->contains(fn (array $n): bool => $n['body'] === 'Body fallback'));
        $this->assertTrue(collect($items)->contains(fn (array $n): bool => $n['read'] === true));
        $this->assertTrue(collect($items)->contains(fn (array $n): bool => $n['read'] === false));
    }
}
