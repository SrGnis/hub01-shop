<?php

namespace App\Livewire;

use App\Models\PendingEmailChange;
use App\Models\PendingPasswordChange;
use App\Notifications\AuthorizeEmailChange;
use App\Notifications\ConfirmPasswordChange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Mary\Traits\Toast;

class UserAccountSecurity extends Component
{
    use Toast;

    public string $current_password = '';
    public string $new_email = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';
    public bool $show_password_form = false;
    public bool $show_email_form = false;
    public ?PendingEmailChange $pending_email_change = null;
    public ?PendingPasswordChange $pending_password_change = null;

    public function mount()
    {
        $this->loadPendingEmailChange();
        $this->loadPendingPasswordChange();
    }

    private function loadPendingEmailChange(): void
    {
        $this->pending_email_change = Auth::user()
            ->pendingEmailChanges()
            ->whereIn('status', ['pending_authorization', 'pending_verification'])
            ->latest()
            ->first();
    }

    private function loadPendingPasswordChange(): void
    {
        $this->pending_password_change = Auth::user()
            ->pendingPasswordChanges()
            ->where('status', 'pending_verification')
            ->latest()
            ->first();
    }

    protected function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'new_password' => ['required', 'string', Password::default(), 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password.',
            'new_email.unique' => 'A error occurred. Please try again.',
            'new_password.confirmed' => 'The passwords do not match.',
        ];
    }

    public function requestEmailChange()
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        if (!Hash::check($this->current_password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        try {
            $user = Auth::user();

            // Cancel any existing pending email changes
            $user->pendingEmailChanges()
                ->whereIn('status', ['pending_authorization', 'pending_verification'])
                ->delete();

            // Create new pending email change
            $authorizationToken = Str::random(64);
            $pendingChange = PendingEmailChange::create([
                'user_id' => $user->id,
                'old_email' => $user->email,
                'new_email' => $this->new_email,
                'authorization_token' => $authorizationToken,
                'status' => 'pending_authorization',
                'authorization_expires_at' => now()->addHour(),
            ]);

            // Send authorization email to current email
            $user->notify(new AuthorizeEmailChange($pendingChange));

            $this->show_email_form = false;
            $this->current_password = '';
            $this->loadPendingEmailChange();
            $this->success('Verification email sent! Check your current email address to authorize the change.');
        } catch (\Exception $e) {
            logger()->error('Failed to request email change', ['error' => $e->getMessage()]);
            $this->error('Failed to request email change. Please try again.');
        }
    }

    public function cancelEmailChange()
    {
        try {
            if ($this->pending_email_change) {
                $this->pending_email_change->delete();
                $this->loadPendingEmailChange();
                $this->success('Email change cancelled.');
            }
        } catch (\Exception) {
            $this->error('Failed to cancel email change.');
        }
    }

    public function requestPasswordChange()
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        if (!Hash::check($this->current_password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        try {
            $user = Auth::user();

            // Cancel any existing pending password changes
            $user->pendingPasswordChanges()
                ->where('status', 'pending_verification')
                ->delete();

            // Create new pending password change
            $verificationToken = Str::random(64);
            $hashedPassword = Hash::make($this->new_password);

            $pendingChange = PendingPasswordChange::create([
                'user_id' => $user->id,
                'hashed_password' => $hashedPassword,
                'verification_token' => $verificationToken,
                'status' => 'pending_verification',
                'expires_at' => now()->addHour(),
            ]);

            // Send confirmation email
            $user->notify(new ConfirmPasswordChange($pendingChange));

            $this->show_password_form = false;
            $this->current_password = '';
            $this->new_password = '';
            $this->new_password_confirmation = '';
            $this->loadPendingPasswordChange();
            $this->success('Confirmation email sent! Check your email to confirm the password change.');
        } catch (\Exception $e) {
            logger()->error('Failed to request password change', ['error' => $e->getMessage()]);
            $this->error('Failed to request password change. Please try again.');
        }
    }

    public function cancelPasswordChange()
    {
        try {
            if ($this->pending_password_change) {
                $this->pending_password_change->delete();
                $this->loadPendingPasswordChange();
                $this->success('Password change cancelled.');
            }
        } catch (\Exception) {
            $this->error('Failed to cancel password change.');
        }
    }

    public function togglePasswordForm()
    {
        $this->show_password_form = !$this->show_password_form;
        if (!$this->show_password_form) {
            $this->resetForm();
        }
    }

    public function toggleEmailForm()
    {
        $this->show_email_form = !$this->show_email_form;
        if (!$this->show_email_form) {
            $this->resetForm();
        }
    }

    private function resetForm()
    {
        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->new_email = Auth::user()->email;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.user-account-security');
    }
}

