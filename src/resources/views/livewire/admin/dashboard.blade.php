<div class="w-full m-auto py-6">
    <x-card>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Admin Dashboard</h1>
            <x-button link="{{ route('admin.site') }}" label="Site Administration" icon="lucide-settings"
                class="btn-primary" />
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <x-stat title="Total Users" description="{{ $stats['admins'] }} admins" value="{{ $stats['users'] }}"
                icon="lucide-users" color="text-primary" />

            <x-stat title="Total Projects" description="{{ $stats['versions'] }} versions"
                value="{{ $stats['projects'] }}" icon="lucide-package" color="text-success" />

            <x-stat title="Total Downloads" description="{{ $stats['files'] }} files"
                value="{{ number_format($stats['downloads']) }}" icon="lucide-download" color="text-secondary" />
        </div>

        {{-- Recent Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Recent Users --}}
            <x-card title="Recent Users" class="bg-base-200">
                <ul class="divide-y divide-base-300">
                    @foreach ($recentUsers as $user)
                        <x-list-item :item="$user">
                            <x-slot:avatar>
                                <x-avatar placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}"
                                    placeholder-text-class="text-sm font-bold"
                                    placeholder-bg-class="bg-primary text-primary-content" class="!w-10"
                                    image="{{ $user->getAvatarUrl() }}">
                                    <x-slot:title class="font-semibold text-sm truncate">
                                        {{ $user->name }}
                                    </x-slot:title>
                                    <x-slot:subtitle class="text-xs text-base-content/60">
                                        {{ $user->email }}
                                    </x-slot:subtitle>
                                </x-avatar>
                            </x-slot:avatar>
                            <x-slot:actions>
                                <span class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</span>
                            </x-slot:actions>
                        </x-list-item>
                    @endforeach
                </ul>
                <x-slot:actions>
                    <x-button link="{{ route('admin.users') }}" label="View all users" icon="lucide-arrow-right"
                        class="btn-sm btn-ghost" />
                </x-slot:actions>
            </x-card>

            {{-- Recent Projects --}}
            <x-card title="Recent Projects" class="bg-base-200">
                <ul class="divide-y divide-base-300">
                    @foreach ($recentProjects as $project)
                        <x-list-item :item="$project">
                            <x-slot:avatar>
                                <x-avatar placeholder="{{ strtoupper(substr($project->name, 0, 1)) }}"
                                    placeholder-text-class="text-sm font-bold"
                                    placeholder-bg-class="bg-secondary text-secondary-content" class="!w-10"
                                    image="{{ $project->logo_path ? Storage::url($project->logo_path) : null }}">
                                    <x-slot:title class="font-semibold text-sm truncate">
                                        {{ $project->name }}
                                    </x-slot:title>
                                    <x-slot:subtitle class="text-xs text-base-content/60">
                                        {{ $project->projectType->display_name }}
                                    </x-slot:subtitle>
                                </x-avatar>
                            </x-slot:avatar>
                            <x-slot:actions>
                                <span class="text-xs text-gray-400">{{ $project->created_at->diffForHumans() }}</span>
                            </x-slot:actions>
                        </x-list-item>
                    @endforeach
                </ul>
                <x-slot:actions>
                    <x-button link="{{ route('admin.projects.index') }}" label="View all projects"
                        icon="lucide-arrow-right" class="btn-sm btn-ghost" />
                </x-slot:actions>
            </x-card>
        </div>
    </x-card>
</div>
