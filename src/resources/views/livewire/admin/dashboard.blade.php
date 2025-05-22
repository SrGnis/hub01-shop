<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">Admin Dashboard</h1>
            <a href="{{ route('admin.site') }}" class="text-indigo-400 hover:text-indigo-300">Site Administration</a>
        </div>
        

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-500 bg-opacity-10">
                        @svg('lucide-users', 'w-8 h-8 text-indigo-500')
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Total Users</p>
                        <p class="text-2xl font-semibold">{{ $stats['users'] }}</p>
                    </div>
                </div>
                <div class="mt-2">
                    <p class="text-sm text-gray-400">{{ $stats['admins'] }} admins</p>
                </div>
            </div>

            <div class="bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-500 bg-opacity-10">
                        @svg('lucide-package', 'w-8 h-8 text-green-500')
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Total Projects</p>
                        <p class="text-2xl font-semibold">{{ $stats['projects'] }}</p>
                    </div>
                </div>
                <div class="mt-2">
                    <p class="text-sm text-gray-400">{{ $stats['versions'] }} versions</p>
                </div>
            </div>

            <div class="bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-500 bg-opacity-10">
                        @svg('lucide-download', 'w-8 h-8 text-purple-500')
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Total Downloads</p>
                        <p class="text-2xl font-semibold">{{ $stats['downloads'] }}</p>
                    </div>
                </div>
                <div class="mt-2">
                    <p class="text-sm text-gray-400">{{ $stats['files'] }} files</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Users -->
            <div class="bg-zinc-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-zinc-700">
                    <h2 class="text-lg font-medium">Recent Users</h2>
                </div>
                <div class="p-6">
                    <ul class="divide-y divide-zinc-700">
                        @foreach($recentUsers as $user)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="font-medium">{{ $user->name }}</p>
                                    <p class="text-sm text-gray-400">{{ $user->email }}</p>
                                </div>
                                <span class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4">
                        <a href="{{ route('admin.users') }}" class="text-indigo-400 hover:text-indigo-300 text-sm">View all users →</a>
                    </div>
                </div>
            </div>

            <!-- Recent Projects -->
            <div class="bg-zinc-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-zinc-700">
                    <h2 class="text-lg font-medium">Recent Projects</h2>
                </div>
                <div class="p-6">
                    <ul class="divide-y divide-zinc-700">
                        @foreach($recentProjects as $project)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="font-medium">{{ $project->name }}</p>
                                    <p class="text-sm text-gray-400">{{ $project->projectType->display_name }}</p>
                                </div>
                                <span class="text-xs text-gray-400">{{ $project->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4">
                        <a href="{{ route('admin.projects') }}" class="text-indigo-400 hover:text-indigo-300 text-sm">View all projects →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
