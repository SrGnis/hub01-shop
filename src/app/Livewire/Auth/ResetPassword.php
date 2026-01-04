<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('components.layouts.auth')]
#[Title('Reset Password')]
class ResetPassword extends Component
{
    use Toast;

    public string $token = '';

    #[Rule('required|string|email')]
    public string $email = '';

    #[Rule('required|string|confirmed')]
    public string $password = '';

    #[Rule('required|string')]
    public string $password_confirmation = '';

    public function mount($token = null, $email = null)
    {
        $this->token = $token ?? request('token', '');
        $this->email = $email ?? request('email', '');

        // if no token, no email, user doesn't exist, or token is invalid, redirect to login
        if (!$this->token || !$this->email || User::where('email', $this->email)->doesntExist() || !Password::tokenExists(User::where('email', $this->email)->first(), $this->token)) {
            Session::flash('error', 'Invalid password reset link.');
            return redirect()->route('login');
        }
    }

    public function resetPassword()
    {
        $this->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's login screen. If there is an error we can redirect
        // them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            $this->success('Your password has been reset! You can now log in.', redirectTo: route('login'));
        } else {
            $this->error('We could not reset your password. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
