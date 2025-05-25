<?php

namespace App\Livewire\Auth;

use App\Livewire\Forms\RegisterForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Register extends Component
{
    public RegisterForm $form;

    // Format examples
    public $usernameExamples = [
        'john.doe',
        'jane_smith',
        'developer-2023',
        'tech.user.42',
    ];

    public function mount()
    {
        if (Auth::check()) {
            return redirect()->intended(route('project-search', \App\Models\ProjectType::first()));
        }
    }

    public function updatedFormName()
    {
        $this->validateOnly('form.name');
    }

    public function updatedFormEmail()
    {
        $this->validateOnly('form.email');
    }

    public function updatedFormPassword()
    {
        $this->validateOnly('form.password');
    }

    public function updatedFormPasswordConfirmation()
    {
        $this->validateOnly('form.password');
    }

    public function register()
    {
        $this->validate();

        $user = $this->form->store();

        session()->regenerate();

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
