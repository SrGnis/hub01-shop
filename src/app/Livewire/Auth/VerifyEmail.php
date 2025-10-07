<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('components.layouts.auth')]
#[Title('Verify Email')]
class VerifyEmail extends Component
{
    use Toast;

    public function resendVerification()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->success('Email already verified!', redirectTo: '/');
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        $this->success('Verification link sent! Check your email.');
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
