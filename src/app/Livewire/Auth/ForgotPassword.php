<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('components.layouts.auth')]
#[Title('Forgot Password')]
class ForgotPassword extends Component
{
    use Toast;

    #[Rule('required|string|email')]
    public string $email = '';

    public bool $emailSent = false;

    public function sendResetLink()
    {
        $this->validate();

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            $this->emailSent = true;
            $this->success('Password reset link sent! Check your email.');
        } else {
            $this->error('We could not find a user with that email address.');
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
