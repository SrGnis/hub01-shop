<?php

namespace Tests\Feature\User\Livewire;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret'),
        ]);

        Livewire::test('auth.login')
            ->set('email', $user->email)
            ->set('password', 'secret')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('project-search', ['projectType' => \App\Models\ProjectType::first()], absolute: false));

        $this->assertAuthenticated();
    }

    #[Test]
    public function users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret'),
        ]);

        Livewire::test('auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret'),
        ]);

        // Ensure clean slate (RateLimiter is cache-backed and can persist within the same test process)
        RateLimiter::clear($this->throttleKey($user->email));

        // 5 failed attempts are allowed, but they increment the limiter
        for ($i = 1; $i <= 5; $i++) {
            Livewire::test('auth.login')
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors('email');
        }

        // 6th attempt should be blocked by throttle (auth.throttle message)
        $component = Livewire::test('auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertStringContainsString(
            'Too many',
            $component->errors()->first('email') ?? ''
        );

        $this->assertGuest();
    }

    #[Test]
    public function deactivated_users_cannot_login_and_are_redirected(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret'),
            'deactivated_at' => now(),
        ]);

        Livewire::test('auth.login')
            ->set('email', $user->email)
            ->set('password', 'secret')
            ->call('login')
            ->assertRedirect(route('account.deactivated', absolute: false));

        $this->assertGuest();
    }

    private function throttleKey(string $email): string
    {
        // Mirrors App\Livewire\Auth\Login::throttleKey()
        return Str::transliterate(Str::lower($email) . '|' . request()->ip());
    }
}
