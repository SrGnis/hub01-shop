<?php

namespace App\Livewire;

use App\Models\PendingEmailChange;
use App\Models\PendingPasswordChange;
use App\Services\ApiTokenService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
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

    // API Token Management
    public bool $show_token_modal = false;
    public bool $show_token_display = false;
    public bool $show_revoke_confirmation = false;
    public string $token_name = '';
    public string $token_expiration = '';
    public ?string $displayed_token = null;
    public ?int $editing_token_id = null;
    public ?string $editing_token_name = null;

    private UserService $userService;
    private ApiTokenService $apiTokenService;

    public function boot(UserService $userService, ApiTokenService $apiTokenService)
    {
        $this->userService = $userService;
        $this->apiTokenService = $apiTokenService;
    }

    public function mount()
    {
        $this->loadPendingEmailChange();
        $this->loadPendingPasswordChange();
        $this->token_expiration = '';
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
        $rules = $this->rules();
        unset($rules['new_password']);

        $this->validate($rules);

        if (!Hash::check($this->current_password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        try {
            $this->userService->requestEmailChange(Auth::user(), $this->new_email);

            $this->show_email_form = false;
            $this->current_password = '';
            $this->loadPendingEmailChange();
            $this->success('Verification email sent! Check your current email address to authorize the change.');
        } catch (\Exception $e) {
            logger()->error('Failed to request email change');
            $this->error('Failed to request email change. Please try again.');
        }

    }

    public function cancelEmailChange()
    {
        try {
            if ($this->pending_email_change) {
                $this->userService->cancelEmailChange($this->pending_email_change);
                $this->loadPendingEmailChange();
                $this->success('Email change cancelled.');
            }
        } catch (\Exception) {
            logger()->error('Failed to cancel email change');
            $this->error('Failed to cancel email change.');
        }
    }

    public function requestPasswordChange()
    {
        $rules = $this->rules();
        unset($rules['new_email']);

        $this->validate($rules);

        if (!Hash::check($this->current_password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        try {
            $this->userService->requestPasswordChange(Auth::user(), $this->new_password);

            $this->show_password_form = false;
            $this->current_password = '';
            $this->new_password = '';
            $this->new_password_confirmation = '';
            $this->loadPendingPasswordChange();
            $this->success('Confirmation email sent! Check your email to confirm the password change.');
        } catch (\Exception $e) {
            logger()->error('Failed to request password change');
            $this->error('Failed to request password change. Please try again.');
        }
    }

    public function cancelPasswordChange()
    {
        try {
            if ($this->pending_password_change) {
                $this->userService->cancelPasswordChange($this->pending_password_change);
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

    // API Token Management Methods

    public function openTokenModal()
    {
        $this->resetTokenForm();
        $this->show_token_modal = true;
    }

    public function closeTokenModal()
    {
        $this->show_token_modal = false;
        $this->resetTokenForm();
    }

    public function closeTokenDisplay()
    {
        $this->show_token_display = false;
        $this->displayed_token = null;
    }

    public function resetTokenForm()
    {
        $this->token_name = '';
        $this->token_expiration = '';
        $this->editing_token_id = null;
        $this->editing_token_name = null;
        $this->resetErrorBag();
    }

    public function createToken()
    {
        $this->validate([
            'token_name' => 'required|string|min:3|max:255',
            'token_expiration' => 'nullable|date|after_or_equal:today',
        ], [
            'token_name.required' => 'Please enter a name for your token.',
            'token_name.min' => 'Token name must be at least 3 characters.',
            'token_expiration.after_or_equal' => 'Expiration date must be today or later.',
        ]);

        try {
            $expirationDate = !empty($this->token_expiration) ? new \DateTime($this->token_expiration) : null;
            $result = $this->apiTokenService->createToken(
                Auth::user(),
                $this->token_name,
                $expirationDate
            );

            $this->displayed_token = $result->plainTextToken;
            $this->show_token_modal = false;
            $this->show_token_display = true;
            $this->resetTokenForm();
            $this->success('API token created successfully!');
        } catch (\Exception $e) {
            logger()->error('Failed to create API token', ['error' => $e->getMessage()]);
            $this->error('Failed to create API token. Please try again.');
        }
    }

    public function confirmRevokeToken(int $tokenId)
    {
        $this->editing_token_id = $tokenId;
        $this->show_revoke_confirmation = true;
    }

    public function revokeToken(int $tokenId)
    {
        try {
            $success = $this->apiTokenService->revokeToken(Auth::user(), $tokenId);

            if ($success) {
                $this->success('API token revoked successfully.');
            } else {
                $this->error('Token not found.');
            }
        } catch (\Exception $e) {
            logger()->error('Failed to revoke API token', ['error' => $e->getMessage()]);
            $this->error('Failed to revoke token. Please try again.');
        }

        $this->editing_token_id = null;
        $this->show_revoke_confirmation = false;
    }

    public function renewToken(int $tokenId)
    {
        $token = $this->apiTokenService->getToken(Auth::user(), $tokenId);

        if (!$token) {
            $this->error('Token not found.');
            return;
        }

        $this->editing_token_id = $tokenId;
        $this->editing_token_name = $token->name;
        $this->token_name = $token->name;
        $this->token_expiration = '';
        $this->show_token_modal = true;
    }

    public function updateTokenExpiration()
    {
        $this->validate([
            'token_expiration' => 'nullable|date|after_or_equal:today',
        ], [
            'token_expiration.after_or_equal' => 'Expiration date must be today or later.',
        ]);

        try {
            $newExpirationDate = !empty($this->token_expiration) ? new \DateTime($this->token_expiration) : null;
            $success = $this->apiTokenService->renewToken(
                Auth::user(),
                $this->editing_token_id,
                $newExpirationDate
            );

            if ($success) {
                $this->success('Token expiration updated successfully.');
            } else {
                $this->error('Token not found.');
            }
        } catch (\Exception $e) {
            logger()->error('Failed to renew API token', ['error' => $e->getMessage()]);
            $this->error('Failed to update token expiration. Please try again.');
        }

        $this->closeTokenModal();
    }

    #[Computed]
    public function tokens()
    {
        return $this->apiTokenService->getUserTokens(Auth::user());
    }

    public function render()
    {
        return view('livewire.user-account-security');
    }
}
