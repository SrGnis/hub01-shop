<?php

namespace App\Livewire\Auth;

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public LoginForm $form;

    public function mount()
    {
        if (Auth::check()) {
            return redirect()->intended(route('project-search', \App\Models\ProjectType::first()));
        }
    }

    public function login()
    {
        $this->validate();

        $this->form->authenticate();

        session()->regenerate();

        return redirect()->intended(route('project-search', \App\Models\ProjectType::first()));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
