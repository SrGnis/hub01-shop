<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class SiteManagement extends Component
{
    public $activeTab = 'project-types';

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.admin.site-management');
    }
}
