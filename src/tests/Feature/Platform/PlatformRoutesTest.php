<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlatformRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string}>
     */
    public static function platformRouteNames(): array
    {
        return [
            ['platform.dashboard'],
            ['platform.notifications'],
            ['platform.collections'],
            ['platform.projects'],
            ['platform.analytics'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('platformRouteNames')]
    public function guest_is_redirected_to_login(string $routeName): void
    {
        $this->get(route($routeName))
            ->assertRedirect(route('login'));
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('platformRouteNames')]
    public function verified_user_can_access_platform_routes(string $routeName): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk();
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('platformRouteNames')]
    public function unverified_user_is_redirected_to_verification_notice(string $routeName): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertRedirect(route('verification.notice'));
    }
}

