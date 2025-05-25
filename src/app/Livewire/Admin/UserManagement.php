<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    // User form
    public $userId = null;

    public $name = '';

    public $email = '';

    public $password = '';

    public $role = 'user';

    public $isEditing = false;

    // Confirmation
    public $confirmingUserDeletion = false;

    public $userToDelete = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'role' => 'required|in:user,admin',
    ];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function createUser()
    {
        $this->resetForm();
        $this->isEditing = false;
    }

    public function editUser(User $user)
    {
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->isEditing = true;
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

            session()->flash('message', 'User updated successfully.');
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'email_verified_at' => now(),
            ]);

            session()->flash('message', 'User created successfully.');
        }

        $this->resetForm();
    }

    public function confirmUserDeletion(User $user)
    {
        $this->confirmingUserDeletion = true;
        $this->userToDelete = $user;
    }

    public function deleteUser()
    {
        $user = $this->userToDelete;

        if ($user) {
            // Don't allow deleting yourself
            if ($user->id === Auth::id()) {
                session()->flash('error', 'You cannot delete your own account.');
                $this->confirmingUserDeletion = false;
                $this->userToDelete = null;

                return;
            }

            $user->delete();
            session()->flash('message', 'User deleted successfully.');
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
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.user-management', [
            'users' => $users,
        ]);
    }

    public function layout()
    {
        return 'components.layouts.admin';
    }
}
