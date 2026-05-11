<?php

namespace Tests\Feature\Platform\Livewire;

use App\Enums\CollectionSystemType;
use App\Enums\CollectionVisibility;
use App\Livewire\Platform\Dashboard;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDailyDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertViewIs('livewire.platform.dashboard');
    }

    #[Test]
    public function profile_payload_contains_expected_keys(): void
    {
        $user = User::factory()->create(['name' => 'Workspace User']);

        $this->actingAs($user);

        $profile = Livewire::test(Dashboard::class)->get('profile');

        $this->assertSame('Workspace User', $profile['name']);
        $this->assertArrayHasKey('profile_url', $profile);
        $this->assertArrayHasKey('avatar_url', $profile);
        $this->assertStringContainsString(route('user.profile', ['user' => $user], false), $profile['profile_url']);
    }

    #[Test]
    public function summary_metrics_contains_downloads_and_favorites_with_integers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 10:00:00'));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $project = Project::factory()->create(['name' => 'Scoped Project']);
        Membership::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'status' => 'active',
        ]);

        $version = ProjectVersion::factory()->withoutDailyDownloads()->create([
            'project_id' => $project->id,
            'name' => 'v1',
            'version' => '1.0.0',
        ]);

        ProjectVersionDailyDownload::factory()->forVersion($version)->forDate('2026-05-10')->withDownloads(44)->create();

        $favorites = \App\Models\Collection::create([
            'user_id' => $otherUser->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        \App\Models\CollectionEntry::create([
            'collection_uid' => $favorites->uid,
            'project_id' => $project->id,
            'sort_order' => 0,
        ]);

        $this->actingAs($user);

        $metrics = Livewire::test(Dashboard::class)->get('summaryMetrics');
        $byKey = collect($metrics)->keyBy('key');

        $this->assertSame(44, $byKey['downloads']['value']);
        $this->assertSame(1, $byKey['favorites']['value']);
        $this->assertIsInt($byKey['downloads']['value']);
        $this->assertIsInt($byKey['favorites']['value']);
    }

    #[Test]
    public function recent_notifications_are_capped_to_five_and_read_flag_maps_from_read_at(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00'));

        $user = User::factory()->create();
        $this->actingAs($user);

        for ($i = 0; $i < 7; $i++) {
            $user->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\Workspace' . $i,
                'data' => ['subject' => 'N' . $i, 'message' => 'Body ' . $i],
                'read_at' => $i % 2 === 0 ? Carbon::now() : null,
                'created_at' => Carbon::now()->subMinutes($i),
                'updated_at' => Carbon::now()->subMinutes($i),
            ]);
        }

        $notifications = Livewire::test(Dashboard::class)->get('recentNotifications');

        $this->assertCount(5, $notifications);
        $this->assertTrue(collect($notifications)->contains(fn (array $row): bool => $row['read'] === true));
        $this->assertTrue(collect($notifications)->contains(fn (array $row): bool => $row['read'] === false));
    }
}
