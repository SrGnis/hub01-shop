<?php

namespace App\Services;

use App\Models\PendingEmailChange;
use App\Models\PendingPasswordChange;
use App\Models\User;
use App\Notifications\AuthorizeEmailChange;
use App\Notifications\ConfirmPasswordChange;
use App\Notifications\UserDeactivated;
use App\Notifications\UserReactivated;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($user, $data, $avatar) {
            if ($avatar) {
                $path = $avatar->store('avatars', 'public');
                $data['avatar'] = $path;
            }

            $user->update($data);

            return $user;
        });
    }

    /**
     * Create a new user (Admin function)
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
            ]);

            $user->markEmailAsVerified();

            return $user;
        });
    }

    /**
     * Update an existing user (Admin function)
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->role = $data['role'] ?? 'user';

            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            return $user;
        });
    }

    /**
     * Delete a user (Admin function)
     */
    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * Deactivate a user (Admin function)
     */
    public function deactivate(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->deactivated_at = now();
            $user->save();

            // Invalidate all user sessions
            DB::table('sessions')->where('user_id', $user->id)->delete();

            // Send notification to the user
            $user->notify(new UserDeactivated);
        });
    }

    /**
     * Reactivate a user (Admin function)
     */
    public function reactivate(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->deactivated_at = null;
            $user->save();

            // Send notification to the user
            $user->notify(new UserReactivated);
        });
    }

    /**
     * Request an email change
     */
    public function requestEmailChange(User $user, string $newEmail): void
    {
        DB::transaction(function () use ($user, $newEmail) {
            // Cancel any existing pending email changes
            $user->pendingEmailChanges()
                ->whereIn('status', ['pending_authorization', 'pending_verification'])
                ->delete();

            // Create new pending email change
            $authorizationToken = Str::random(64);
            $pendingChange = PendingEmailChange::create([
                'user_id' => $user->id,
                'old_email' => $user->email,
                'new_email' => $newEmail,
                'authorization_token' => $authorizationToken,
                'status' => 'pending_authorization',
                'authorization_expires_at' => now()->addHour(),
            ]);

            // Send authorization email to current email
            $user->notify(new AuthorizeEmailChange($pendingChange));
        });
    }

    /**
     * Cancel an email change
     */
    public function cancelEmailChange(PendingEmailChange $pendingEmailChange): void
    {
        $pendingEmailChange->delete();
    }

    /**
     * Request a password change
     */
    public function requestPasswordChange(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword) {
            // Cancel any existing pending password changes
            $user->pendingPasswordChanges()
                ->where('status', 'pending_verification')
                ->delete();

            // Create new pending password change
            $verificationToken = Str::random(64);
            $hashedPassword = Hash::make($newPassword);

            $pendingChange = PendingPasswordChange::create([
                'user_id' => $user->id,
                'hashed_password' => $hashedPassword,
                'verification_token' => $verificationToken,
                'status' => 'pending_verification',
                'expires_at' => now()->addHour(),
            ]);

            // Send confirmation email
            $user->notify(new ConfirmPasswordChange($pendingChange));
        });
    }

    /**
     * Cancel a password change
     */
    public function cancelPasswordChange(PendingPasswordChange $pendingPasswordChange): void
    {
        $pendingPasswordChange->delete();
    }
}
