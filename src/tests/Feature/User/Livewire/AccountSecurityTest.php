<?php

namespace Tests\Feature\User\Livewire;

use App\Livewire\UserAccountSecurity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AuthorizeEmailChange;
use App\Notifications\ConfirmPasswordChange;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_email_change_can_be_requested()
    {
        Notification::fake();
        $user = User::factory()->create(['password' => Hash::make('password')]);

        Livewire::actingAs($user)
            ->test(UserAccountSecurity::class)
            ->set('current_password', 'password')
            ->set('new_email', 'new@example.com')
            ->call('requestEmailChange')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pending_email_changes', [
            'user_id' => $user->id,
            'new_email' => 'new@example.com',
            'status' => 'pending_authorization',
        ]);

        Notification::assertSentTo($user, AuthorizeEmailChange::class);
    }

    #[Test]
    public function test_password_change_can_be_requested()
    {
        Notification::fake();
        $user = User::factory()->create(['password' => Hash::make('password')]);

        Livewire::actingAs($user)
            ->test(UserAccountSecurity::class)
            ->set('current_password', 'password')
            ->set('new_password', 'new-password')
            ->set('new_password_confirmation', 'new-password')
            ->call('requestPasswordChange')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pending_password_changes', [
            'user_id' => $user->id,
            'status' => 'pending_verification',
        ]);

        Notification::assertSentTo($user, ConfirmPasswordChange::class);
    }

    #[Test]
    public function test_current_password_is_required_for_email_change()
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        Livewire::actingAs($user)
            ->test(UserAccountSecurity::class)
            ->set('current_password', 'wrong-password')
            ->set('new_email', 'new@example.com')
            ->call('requestEmailChange')
            ->assertHasErrors(['current_password']);
    }

    #[Test]
    public function test_current_password_is_required_for_password_change()
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        Livewire::actingAs($user)
            ->test(UserAccountSecurity::class)
            ->set('current_password', 'wrong-password')
            ->set('new_password', 'new-password')
            ->set('new_password_confirmation', 'new-password')
            ->call('requestPasswordChange')
            ->assertHasErrors(['current_password']);
    }

    #[Test]
    public function test_email_change_can_be_cancelled()
    {
        $user = User::factory()->create();
        $user->pendingEmailChanges()->create([
            'old_email' => $user->email,
            'new_email' => 'cancel@example.com',
            'authorization_token' => 'token',
            'status' => 'pending_authorization',
            'authorization_expires_at' => now()->addHour(),
        ]);

        Livewire::actingAs($user)
            ->test(UserAccountSecurity::class)
            ->call('cancelEmailChange')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('pending_email_changes', [
            'user_id' => $user->id,
            'new_email' => 'cancel@example.com',
        ]);
    }

    #[Test]
    public function test_password_change_can_be_cancelled()
    {
        $user = User::factory()->create();
        $user->pendingPasswordChanges()->create([
            'hashed_password' => 'hash',
            'verification_token' => 'token',
            'status' => 'pending_verification',
            'expires_at' => now()->addHour(),
        ]);

        Livewire::actingAs($user)
            ->test(UserAccountSecurity::class)
            ->call('cancelPasswordChange')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('pending_password_changes', [
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function test_forms_can_be_toggled()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserAccountSecurity::class)
            ->assertSet('show_email_form', false)
            ->assertSet('show_password_form', false)
            ->call('toggleEmailForm')
            ->assertSet('show_email_form', true)
            ->call('toggleEmailForm')
            ->assertSet('show_email_form', false)
            ->call('togglePasswordForm')
            ->assertSet('show_password_form', true)
            ->call('togglePasswordForm')
            ->assertSet('show_password_form', false);
    }
}
