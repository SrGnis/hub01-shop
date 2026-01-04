@props(['project'])

<x-card title="Creators" separator>
    @if($project->users->count() > 0)
        <div class="space-y-3">
            @foreach($project->active_users as $user)
                <a href="{{ route('user.profile', $user) }}" class="block pb-3 border-b border-base-content/10 last:border-b-0 last:pb-0 hover:bg-base-200/50 -mx-2 px-2 py-2 rounded transition-colors">
                    <div class="flex items-center justify-between gap-3">
                        <x-avatar
                            placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}"
                            placeholder-text-class="text-sm font-bold"
                            placeholder-bg-class="bg-primary text-primary-content"
                            class="!w-10"
                            image="{{ $user->getAvatarUrl() }}"
                        >
                            <x-slot:title class="font-semibold text-sm truncate">
                                {{ $user->name }}
                            </x-slot:title>
                            <x-slot:subtitle class="text-xs text-base-content/60">
                                {{ ucfirst($user->pivot->role) }}
                            </x-slot:subtitle>
                        </x-avatar>

                        @if($user->pivot->primary)
                            <x-badge value="Primary" class="badge-primary badge-sm" />
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p class="text-base-content/60 italic text-sm">No creators found</p>
    @endif
</x-card>

