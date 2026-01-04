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

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $terms = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:users|regex:/^[A-Za-z0-9\.\-_]+$/',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'terms' => 'accepted',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.regex' => 'The username can only contain letters, numbers, dots, underscores, and hyphens.',
        ];
    }

    public function register()
    {
        $this->validate();

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
