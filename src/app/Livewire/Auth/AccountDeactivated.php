<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Account Deactivated')]
class AccountDeactivated extends Component
{
    public function render()
    {
        return view('livewire.auth.account-deactivated');
    }
}

