<?php

namespace App\Livewire;

use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class UserProfileEdit extends Component
{
    public User $user;

    public $name;
    public $email;
    public $bio;
    public $current_password;
    public $password;
    public $password_confirmation;

    public function mount(User $user)
    {

        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please log in.');
        }

        if (Auth::id() !== $user->id) {
            return redirect()->route('user.profile', $user)
                ->with('error', 'You can only edit your own profile.');
        }

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->bio = $user->bio;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $this->user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'bio' => 'nullable|string|max:1000',
        ]);

        $this->user->name = $this->name;
        $this->user->email = $this->email;
        $this->user->bio = $this->bio;
        $this->user->save();

        session()->flash('message', 'Profile updated successfully!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->user->password = Hash::make($this->password);
        $this->user->save();

        $this->user->notify(new PasswordChanged());

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('message', 'Password updated successfully!');
    }

    public function render()
    {
        return view('livewire.user-profile-edit');
    }
}
