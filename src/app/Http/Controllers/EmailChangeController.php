<?php

namespace App\Http\Controllers;

use App\Models\PendingEmailChange;
use App\Notifications\EmailChangeCompleted;
use App\Notifications\VerifyNewEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class EmailChangeController extends Controller
{
    /**
     * Authorize the email change from the current email address
     */
    public function authorize(Request $request, string $token)
    {
        $pendingChange = PendingEmailChange::where('authorization_token', $token)
            ->where('user_id', Auth::id())
            ->first();

        if (!$pendingChange) {
            return redirect('/profile/edit')->with('error', 'Invalid or expired authorization link.');
        }

        if (!$pendingChange->isAuthorizationTokenValid()) {
            return redirect('/profile/edit')->with('error', 'Authorization link has expired. Please request a new email change.');
        }

        try {
            // Generate verification token
            $verificationToken = Str::random(64);

            // Mark as authorized and generate verification token
            $pendingChange->markAsAuthorized($verificationToken);

            // Send verification email to new email address
            Notification::route('mail', $pendingChange->new_email)
                ->notify(new VerifyNewEmail($pendingChange));

            return redirect('/profile/edit')->with('success', 'Email change authorized! Check your new email address to complete the verification.');
        } catch (\Exception $e) {
            logger()->error('Failed to authorize email change', [
                'error' => $e->getMessage(),
                'pending_change_id' => $pendingChange->id ?? null,
            ]);
            return redirect('/profile/edit')->with('error', 'Failed to authorize email change. Please try again.');
        }
    }

    /**
     * Verify the new email address
     */
    public function verify(Request $request, string $token)
    {
        $pendingChange = PendingEmailChange::where('verification_token', $token)
            ->where('user_id', Auth::id())
            ->first();

        if (!$pendingChange) {
            return redirect('/profile/edit')->with('error', 'Invalid or expired verification link.');
        }

        if (!$pendingChange->isVerificationTokenValid()) {
            return redirect('/profile/edit')->with('error', 'Verification link has expired. Please request a new email change.');
        }

        try {
            $user = Auth::user();

            $oldEmail = $user->email;
            $newEmail = $pendingChange->new_email;

            // Update user email and reset verification
            $user->email = $newEmail;
            $user->email_verified_at = now();
            $user->save();

            // Mark email change as completed
            $pendingChange->markAsVerified();

            // Send completion notification to both old and new email addresses
            // Send to old email
            Notification::route('mail', $oldEmail)
                ->notify(new EmailChangeCompleted($oldEmail, $newEmail));

            return redirect('/profile/edit')->with('success', 'Email address verified successfully! You can now login with your new email address.');
        } catch (\Exception $e) {
            logger()->error('Failed to verify email change', [
                'error' => $e->getMessage(),
                'pending_change_id' => $pendingChange->id ?? null,
                'user_id' => Auth::id(),
            ]);
            return redirect('/profile/edit')->with('error', 'Failed to verify email change. Please try again.');
        }
    }
}

