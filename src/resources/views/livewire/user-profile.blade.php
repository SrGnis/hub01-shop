@php
    use Illuminate\Support\Facades\Auth;
@endphp

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
        <!-- User Profile Header -->
        <div class="bg-zinc-800 shadow-md overflow-hidden mb-8 flex flex-col lg:flex-row justify-between items-center rounded-md">
            <div class="p-6 lg:p-8 flex flex-col">
                <div class="flex flex-col lg:flex-row items-center gap-6">
                    <!-- User Avatar -->
                    <div class="w-24 h-24 bg-zinc-700 rounded-full flex items-center justify-center text-3xl font-bold text-white">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>

                    <!-- User Info -->
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-2xl font-bold text-white mb-2">{{ $user->name }}</h1>
                        <p class="text-gray-400 mb-2">Member since {{ $user->created_at->format('F Y') }}</p>

                        @if($user->bio)
                        <div class="mb-4 text-gray-300 text-sm">
                            {{ $user->bio }}
                        </div>
                        @else
                        <div class="mb-4"></div>
                        @endif

                        <!-- User Stats -->
                        <div class="flex flex-wrap justify-center lg:justify-start gap-4 text-sm">
                            <div class="bg-zinc-700 rounded-md px-3 py-1">
                                <span class="font-semibold text-indigo-400">{{ $activeProjects->where('pivot.primary', true)->count() }}</span>
                                <span class="text-gray-300">{{ Str::plural('Project', $activeProjects->where('pivot.primary', true)->count()) }} Owned</span>
                            </div>
                            <div class="bg-zinc-700 rounded-md px-3 py-1">
                                <span class="font-semibold text-indigo-400">{{ $activeProjects->where('pivot.primary', false)->count() }}</span>
                                <span class="text-gray-300">{{ Str::plural('Contribution', $activeProjects->where('pivot.primary', false)->count()) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="m-4 self-center lg:self-baseline">
                @if(Auth::check() && Auth::id() === $user->id)
                    <a href="{{ route('user.profile.edit', $user) }}" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        @svg('lucide-edit-3', 'w-4 h-4 mr-1')
                        Edit Profile
                    </a>
                @endif
            </div>
        </div>

        <!-- User's Projects -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Projects -->
            <div class="lg:col-span-3">
                <!-- Active Projects -->
                @if($activeProjects->count() > 0)
                    <h2 class="text-xl font-bold text-white mb-4">Active Projects</h2>
                    @foreach($activeProjects as $project)
                        <x-project-card :project="$project" />
                    @endforeach
                @endif

                <!-- Deleted Projects -->
                @if($deletedProjects->count() > 0)
                    <h2 class="text-xl font-bold text-red-500 mt-8 mb-4">Deleted Projects</h2>
                    <p class="text-gray-400 text-sm mb-4">These projects have been deleted and will be permanently removed after 14 days.</p>
                    @foreach($deletedProjects as $project)
                        <x-deleted-project-card :project="$project" />
                    @endforeach
                @endif

                @if($activeProjects->count() === 0 && $deletedProjects->count() === 0)
                    <p class="text-gray-400 italic">No projects found</p>
                @endif
            </div>
        </div>
    </div>
</div>
