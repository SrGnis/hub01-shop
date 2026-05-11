<?php

namespace Tests\Feature\Platform\Livewire;

use App\Livewire\Platform\NotificationsComingSoon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationsComingSoonTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(NotificationsComingSoon::class)
            ->assertOk()
            ->assertSee('Coming Soon');
    }

    #[Test]
    public function route_resolves_placeholder_content(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.notifications'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Coming Soon');
    }
}

