<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MembershipController extends Controller
{
    /**
     * Accept a membership invitation
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(Membership $membership)
    {
        Log::info('Membership accept request received', [
            'membership_id' => $membership->id,
            'status' => $membership->status,
            'user_id' => $membership->user_id,
            'current_user' => Auth::id(),
        ]);

        if ($membership->status !== 'pending') {
            Log::warning('Attempted to accept non-pending membership', [
                'membership_id' => $membership->id,
                'status' => $membership->status,
            ]);

            return redirect()->route('project-search', ['projectType' => 'mod'])
                ->with('error', 'This invitation is no longer valid.');
        }

        if (! Auth::check() || Auth::id() !== $membership->user_id) {
            Log::warning('User authentication failed for membership acceptance', [
                'membership_id' => $membership->id,
                'membership_user_id' => $membership->user_id,
                'is_authenticated' => Auth::check(),
                'current_user_id' => Auth::id(),
            ]);

            return redirect()->route('login')
                ->with('error', 'You need to log in to accept this invitation.');
        }

        $membership->update(['status' => 'active']);

        return redirect()->route('project.show', [
            'projectType' => $membership->project->projectType,
            'project' => $membership->project,
        ])->with('message', 'You have successfully joined the project!');
    }

    /**
     * Reject a membership invitation
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Membership $membership)
    {
        Log::info('Membership reject request received', [
            'membership_id' => $membership->id,
            'status' => $membership->status,
            'user_id' => $membership->user_id,
            'current_user' => Auth::id(),
        ]);

        if ($membership->status !== 'pending') {
            Log::warning('Attempted to reject non-pending membership', [
                'membership_id' => $membership->id,
                'status' => $membership->status,
            ]);

            return redirect()->route('project-search', ['projectType' => 'mod'])
                ->with('error', 'This invitation is no longer valid.');
        }

        if (! Auth::check() || Auth::id() !== $membership->user_id) {
            return redirect()->route('login')
                ->with('error', 'You need to log in to reject this invitation.');
        }

        $membership->update(['status' => 'rejected']);

        return redirect()->route('project-search', ['projectType' => 'mod'])
            ->with('message', 'You have rejected the invitation.');
    }
}
