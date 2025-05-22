<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function logout()
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        return <<<'BLADE'
            <form wire:submit="logout">
                <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-300 hover:bg-zinc-600 hover:text-white">
                    Sign out
                </button>
            </form>
        BLADE;
    }
}
