<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        if ($this->isEditing) {
            $user = User::find($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->role = $this->role;

            if (! empty($this->password)) {
                $user->password = Hash::make($this->password);
            }

            $user->save();

            $this->success('User updated successfully.');
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'email_verified_at' => now(),
            ]);

            $this->success('User created successfully.');
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

            $user->delete();
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

    public function render()
    {
        $users = User::where('name', 'like', '%'.$this->search.'%')
            ->orWhere('email', 'like', '%'.$this->search.'%')
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);

        return view('livewire.admin.user-management', [
            'users' => $users,
        ]);
    }
}
