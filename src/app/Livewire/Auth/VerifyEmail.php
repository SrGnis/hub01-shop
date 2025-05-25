<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VerifyEmail extends Component
{
    public function mount()
    {
        if (! Auth::user() || Auth::user()->hasVerifiedEmail()) {
            return redirect()->intended(route('project-search', \App\Models\ProjectType::first()));
        }
    }

    public function sendVerificationEmail()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->intended(route('project-search', \App\Models\ProjectType::first()));
        }

        Auth::user()->sendEmailVerificationNotification();

        // TODO: this dont work
        $this->dispatch('status', 'verification-link-sent');
    }

    public function logout()
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
