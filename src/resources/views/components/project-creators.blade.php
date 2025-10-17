@props(['project'])

<x-card title="Creators" separator>
    @if($project->users->count() > 0)
        <div class="space-y-3">
            @foreach($project->active_users as $user)
                <div class="flex items-center gap-3 pb-3 border-b border-base-content/10 last:border-b-0 last:pb-0">
                    <a href="{{ route('user.profile', $user) }}" class="flex-shrink-0">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-sm font-bold text-primary-content hover:opacity-80 transition-opacity">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    </a>
                    <div class="flex-grow min-w-0">
                        <div class="font-semibold text-sm truncate">
                            <a href="{{ route('user.profile', $user) }}" class="hover:text-primary transition-colors">
                                {{ $user->name }}
                            </a>
                        </div>
                        <div class="text-xs text-base-content/60">
                            {{ ucfirst($user->pivot->role) }}
                        </div>
                    </div>
                    @if($user->pivot->primary)
                        <x-badge label="Primary" class="badge-primary badge-sm" />
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-base-content/60 italic text-sm">No creators found</p>
    @endif
</x-card>

