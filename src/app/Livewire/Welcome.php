<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('components.layouts.welcome')]
class Welcome extends Component
{
    use Toast;

    public function render()
    {
        return view('livewire.welcome');
    }
}
