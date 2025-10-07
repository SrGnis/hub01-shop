<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('components.layouts.auth')]
#[Title('Confirm Password')]
class ConfirmPassword extends Component
{
    use Toast;

    #[Rule('required|string')]
    public string $password = '';

    public function confirmPassword()
    {
        $this->validate();

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password does not match your current password.'],
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->success('Password confirmed!', redirectTo: session()->pull('url.intended', '/'));
    }

    public function render()
    {
        return view('livewire.auth.confirm-password');
    }
}
