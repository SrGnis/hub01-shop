@props(['project'])

<div class="bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
    <h2 class="text-xl font-bold mb-4">Creators</h2>
    <div class="creators-list space-y-4">
        @if($project->users->count() > 0)
            @foreach($project->active_users as $user)
                <div class="creator-item flex items-center gap-3 border-b border-zinc-700 pb-3 last:border-b-0">
                    <a href="{{ route('user.profile', $user) }}" class="creator-avatar">
                        <div class="w-12 h-12 bg-zinc-700 rounded-full flex items-center justify-center text-lg font-bold hover:bg-zinc-600 transition-colors">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    </a>
                    <div class="creator-info flex-grow">
                        <div class="font-semibold">
                            <a href="{{ route('user.profile', $user) }}" class="hover:text-indigo-400 transition-colors">{{ $user->name }}</a>
                        </div>
                        <div class="text-sm text-gray-400">{{ ucfirst($user->pivot->role) }}</div>
                    </div>
                    @if($user->pivot->primary)
                        <div class="creator-badge">
                            <span class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">Primary</span>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="text-gray-400 italic">No creators found</div>
        @endif
    </div>
</div>
