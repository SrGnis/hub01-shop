<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Notifications\UserDeactivated;
use App\Notifications\UserReactivated;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('components.layouts.admin')]
class UserManagement extends Component
{
    use WithPagination, Toast;

    public $search = '';

    public $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public $perPage = 10;

    // User form
    public $userId = null;

    public $name = '';

    public $email = '';

    public $password = '';

    public $role = 'user';

    public $isEditing = false;

    // Modal state
    public $showModal = false;

    // Confirmation
    public $confirmingUserDeletion = false;

    public $userToDelete = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'role' => 'required|in:user,admin',
    ];

    private UserService $userService;

    public function boot(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function createUser()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function editUser($user_id)
    {
        $user = User::find($user_id);
        if (! $user) {
            $this->error('User not found.');

            return;
        }
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function saveUser()
    {
        if ($this->isEditing) {
            $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,'.$this->userId,
                'role' => 'required|in:user,admin',
            ]);
        } else {
            $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required|min:8',
                'role' => 'required|in:user,admin',
            ]);
        }

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'password' => $this->password,
            ];

            if ($this->isEditing) {
                $user = User::findOrFail($this->userId);
                $this->userService->update($user, $data);
                $this->success('User updated successfully.');
            } else {
                $newUser = $this->userService->create($data);
                Log::info('User created by admin', [
                    'created_user_id' => $newUser->id,
                    'admin_id' => Auth::id(),
                ]);
                $this->success('User created successfully.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to save user', [
                'user_id' => $this->userId,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to save user: ' . $e->getMessage());
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function confirmUserDeletion($user_id)
    {
        $user = User::find($user_id);
        if (! $user) {
            $this->error('User not found.');

            return;
        }

        $this->confirmingUserDeletion = true;
        $this->userToDelete = $user;
    }

    public function deleteUser()
    {
        $user = $this->userToDelete;

        if ($user) {
            // Don't allow deleting yourself
            if ($user->id === Auth::id()) {
                $this->error('You cannot delete your own account.');
                $this->confirmingUserDeletion = false;
                $this->userToDelete = null;

                return;
            }

            $this->userService->delete($user);
            $this->success('User deleted successfully.');
        }

        $this->confirmingUserDeletion = false;
        $this->userToDelete = null;
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'user';
        $this->isEditing = false;
    }

    public function deactivateUser($userId)
    {
        $user = User::find($userId);
        if (! $user) {
            $this->error('User not found.');

            return;
        }

        // Don't allow deactivating yourself
        if ($user->id === Auth::id()) {
            $this->error('You cannot deactivate your own account.');

            return;
        }

        $this->userService->deactivate($user);
        $this->success('User deactivated successfully.');
    }

    public function reactivateUser($userId)
    {
        $user = User::find($userId);
        if (! $user) {
            $this->error('User not found.');

            return;
        }

        $this->userService->reactivate($user);
        $this->success('User reactivated successfully.');
    }

    public function render()
    {
        /** @disregard P1006, P1005 */
        $users = User::searchScope($this->search)
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);

        return view('livewire.admin.user-management', [
            'users' => $users,
        ]);
    }
}
