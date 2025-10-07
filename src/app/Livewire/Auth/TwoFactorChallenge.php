<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('components.layouts.auth')]
#[Title('Two-Factor Authentication')]
class TwoFactorChallenge extends Component
{
    use Toast;

    #[Rule('nullable|string')]
    public string $code = '';

    #[Rule('nullable|string')]
    public string $recovery_code = '';

    public bool $recovery = false;

    public function authenticate()
    {
        $this->validate([
            'code' => $this->recovery ? 'nullable' : 'required|string',
            'recovery_code' => $this->recovery ? 'required|string' : 'nullable',
        ]);

        $user = session('login.id') 
            ? Auth::guard('web')->getProvider()->retrieveById(session('login.id'))
            : null;

        if (! $user) {
            $this->error('Authentication session expired. Please try again.');
            return redirect()->route('login');
        }

        if ($this->recovery) {
            if (! $user->replaceRecoveryCode($this->recovery_code)) {
                $this->addError('recovery_code', 'The provided recovery code is invalid.');
                return;
            }
        } else {
            if (! $user->validateTwoFactorCode($this->code)) {
                $this->addError('code', 'The provided two factor authentication code is invalid.');
                return;
            }
        }

        Auth::guard('web')->login($user, session('login.remember', false));

        session()->forget([
            'login.id',
            'login.remember',
        ]);

        $this->success('Welcome back!', redirectTo: '/');
    }

    public function toggleRecovery()
    {
        $this->recovery = ! $this->recovery;
        $this->reset(['code', 'recovery_code']);
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge');
    }
}
