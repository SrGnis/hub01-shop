<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    #[Test]
    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertSuccessful();
    }

    #[Test]
    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test('auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendResetLink');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test('auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendResetLink');

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function ($notification) use ($user) {
                $response = $this->get(route('password.reset', [
                    'token' => $notification->token,
                    'email' => $user->email,
                ]));

                $response->assertSuccessful();

                return true;
            }
        );
    }

    #[Test]
    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test('auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendResetLink');

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function ($notification) use ($user) {
                $response = Livewire::test('auth.reset-password', [
                        'token' => $notification->token,
                        'email' => $user->email,
                    ])
                    ->set('email', $user->email)
                    ->set('password', 'password')
                    ->set('password_confirmation', 'password')
                    ->call('resetPassword');

                $response
                    ->assertHasNoErrors()
                    ->assertRedirect(route('login', absolute: false));

                return true;
            }
        );
    }

    #[Test]
    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $response = Livewire::test('auth.reset-password', [
            'token' => 'invalid-token',
        ]);

        $response->assertRedirect(route('login', absolute: false));
    }
}
