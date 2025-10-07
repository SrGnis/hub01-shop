<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('components.layouts.auth')]
#[Title('Register')]
class Register extends Component
{
    use Toast;

    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('required|string|email|max:255|unique:users')]
    public string $email = '';

    #[Rule('required|string|confirmed')]
    public string $password = '';

    #[Rule('required|string')]
    public string $password_confirmation = '';

    #[Rule('accepted')]
    public bool $terms = false;

    public function register()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'terms' => 'accepted',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->success('Welcome! Your account has been created.', redirectTo: '/');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
