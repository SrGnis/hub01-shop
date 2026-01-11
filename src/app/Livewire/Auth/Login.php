<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('components.layouts.auth')]
#[Title('Login')]
class Login extends Component
{
    use Toast;

    #[Rule('required|string|email')]
    public string $email = '';

    #[Rule('required|string')]
    public string $password = '';

    #[Rule('boolean')]
    public bool $remember = false;

    public function login()
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        Log::info('Login attempt', [
            'email' => $this->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            Log::warning('Login failed - invalid credentials', [
                'email' => $this->email,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Check if the user is deactivated
        if (Auth::user()->isDeactivated()) {
            Auth::logout();

            Log::warning('Login failed - user deactivated', [
                'user_id' => Auth::id(),
                'email' => $this->email,
                'ip' => request()->ip(),
            ]);

            return $this->redirect(route('account.deactivated'));
        }

        RateLimiter::clear($this->throttleKey());

        Log::info('User logged in', [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
            'ip' => request()->ip(),
        ]);

        session()->regenerate();

        $this->success('Welcome back!', redirectTo: route('project-search', \App\Models\ProjectType::first()));
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
