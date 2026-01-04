<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertSuccessful();
    }

    #[Test]
    public function users_can_logout(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = User::factory()->create();

        /** @disregard P1006 */
        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('welcome', absolute: false));

        $this->assertGuest();
    }
}
