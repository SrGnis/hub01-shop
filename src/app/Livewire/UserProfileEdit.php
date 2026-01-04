<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

#[Title('Edit Profile')]
class UserProfileEdit extends Component
{
    use Toast, WithFileUploads;

    public User $user;

    public string $bio = '';
    public $avatar;

    private UserService $userService;

    public function boot(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function mount()
    {
        $this->user = Auth::user();
        $this->bio = $this->user->bio ?? '';
    }

    protected function rules(): array
    {
        return [
            'bio' => 'nullable|string|max:125',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    protected function messages(): array
    {
        return [
            'avatar.max' => 'The avatar must not exceed 2MB.',
            'avatar.mimes' => 'The avatar must be a JPEG, PNG, JPG, or GIF image.',
        ];
    }

    public function save()
    {
        $this->validate();

        try {
            $this->userService->updateProfile($this->user, ['bio' => $this->bio], $this->avatar);

            $this->success('Profile updated successfully!');
        } catch (\Exception) {
            logger()->error('Failed to update profile', [
                'user_id' => $this->user->id,
                'bio' => $this->bio,
                'avatar' => $this->avatar,
            ]);
            $this->error('Failed to update profile. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.user-profile-edit');
    }
}

