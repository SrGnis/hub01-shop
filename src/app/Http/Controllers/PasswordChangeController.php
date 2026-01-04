<?php

namespace App\Http\Controllers;

use App\Models\PendingPasswordChange;
use App\Notifications\PasswordChangeCompleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordChangeController extends Controller
{
    /**
     * Verify and apply the password change
     */
    public function verify(string $token)
    {
        $pendingChange = PendingPasswordChange::where('verification_token', $token)
            ->where('user_id', Auth::id())
            ->first();

        if (!$pendingChange) {
            return redirect('/profile/edit')->with('error', 'Invalid or expired password change link.');
        }

        if (!$pendingChange->isVerificationTokenValid()) {
            $pendingChange->delete();
            return redirect('/profile/edit')->with('error', 'Password change link has expired. Please request a new password change.');
        }

        try {
            $user = Auth::user();

            // Update the password
            $user->password = $pendingChange->hashed_password;
            $user->save();

            // Mark password change as verified
            $pendingChange->markAsVerified();

            // Send completion notification
            $user->notify(new PasswordChangeCompleted());

            return redirect('/profile/edit')->with('success', 'Password changed successfully!');
        } catch (\Exception $e) {
            logger()->error('Failed to verify password change', ['error' => $e->getMessage()]);
            return redirect('/profile/edit')->with('error', 'Failed to change password. Please try again.');
        }
    }
}

