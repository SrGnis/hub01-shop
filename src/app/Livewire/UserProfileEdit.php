<?php

namespace App\Livewire;

use App\Models\User;
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
            $data = ['bio' => $this->bio];

            if ($this->avatar) {
                $path = $this->avatar->store('avatars', 'public');
                $data['avatar'] = $path;
            }

            $this->user->update($data);

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

