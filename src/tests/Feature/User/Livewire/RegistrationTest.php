<?php

namespace Tests\Feature\User\Livewire;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    #[Test]
    public function test_new_users_can_register()
    {
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'TestUser')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', true)
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/');
    }

    #[Test] 
    public function test_registration_validation()
    {
        // Seed an existing user to test uniqueness
        User::factory()->create([
            'name'  => 'ExistingUser',
            'email' => 'existing@example.com',
        ]);

        // 1) Email must be valid
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'TestUser')
            ->set('email', 'not-an-email')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['email' => 'email']);

        // 2) Name required
        Session::start();
        Livewire::test('auth.register')
            ->set('name', '')
            ->set('email', 'valid@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['name' => 'required']);

        // 3) Name unique
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'ExistingUser')
            ->set('email', 'unique-email@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['name' => 'unique']);

        // 4) Name regex: /^[A-Za-z0-9\.\-_]+$/
        // invalid because it contains a space and exclamation
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'Bad Name!')
            ->set('email', 'regex@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['name']);

        // 5) Email required
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'EmailRequiredUser')
            ->set('email', '')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['email' => 'required']);

        // 6) Email unique
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'UniqueEmailUser')
            ->set('email', 'existing@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);

        // 7) Password required
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'PasswordRequiredUser')
            ->set('email', 'pw-required@example.com')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['password' => 'required']);

        // 8) Password confirmed
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'PasswordConfirmUser')
            ->set('email', 'pw-confirm@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'different')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['password' => 'confirmed']);

        // 9) Terms accepted
        Session::start();
        Livewire::test('auth.register')
            ->set('name', 'TermsUser')
            ->set('email', 'terms@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', false)
            ->call('register')
            ->assertHasErrors(['terms' => 'accepted']);

    }
}
