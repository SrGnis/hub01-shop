<?php

namespace App\Livewire\Admin;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectVersion;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $stats = [
            'users' => User::count(),
            'projects' => Project::count(),
            'versions' => ProjectVersion::count(),
            'files' => ProjectFile::count(),
            'downloads' => ProjectVersion::sum('downloads'),
            'admins' => User::where('role', 'admin')->count(),
        ];

        $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get();
        $recentProjects = Project::orderBy('created_at', 'desc')->take(5)->get();

        return view('livewire.admin.dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentProjects' => $recentProjects,
        ]);
    }

    public function layout()
    {
        return 'components.layouts.admin';
    }
}
