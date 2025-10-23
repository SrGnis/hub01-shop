<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- User Profile Header -->
    <x-card class="mb-6">
        <div class="flex flex-col md:flex-row gap-6 items-start">
            <!-- User Avatar with Info -->
            <div class="w-full md:max-w-2xl">
                <x-avatar
                    placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}"
                    placeholder-text-class="text-3xl font-bold"
                    placeholder-bg-class="bg-primary text-primary-content"
                    class="!w-24"
                >
                    <x-slot:title class="text-3xl !font-bold pl-2">
                        {{ $user->name }}
                    </x-slot:title>

                    <x-slot:subtitle class="grid gap-2 mt-2 pl-2">
                        @if($user->bio)
                            <p class="text-sm text-base-content/80">{{ $user->bio }}</p>
                        @endif

                        <div class="flex flex-wrap gap-3 text-xs">
                            <x-icon name="lucide-calendar" label="Member since {{ $user->created_at->format('F Y') }}" />
                            <x-icon name="lucide-package" label="{{ $this->ownedProjectsCount }} {{ Str::plural('project', $this->ownedProjectsCount) }} owned" />
                            <x-icon name="lucide-users" label="{{ $this->contributionsCount }} {{ Str::plural('contribution', $this->contributionsCount) }}" />
                        </div>
                    </x-slot:subtitle>
                </x-avatar>
            </div>

            <!-- User Actions -->
            <div class="flex-grow">
                <div class="flex justify-end">
                    @auth
                        @if(auth()->id() === $user->id)
                            <x-button
                                link="{{ route('user.profile.edit', $user) }}"
                                icon="lucide-pencil"
                                class="btn-primary"
                            >
                                Edit Profile
                            </x-button>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </x-card>

    <!-- Active Projects -->
    @if($this->activeProjects->count() > 0)
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">Projects</h2>
            <div class="space-y-4">
                @foreach($this->activeProjects as $project)
                    <x-project-card :project="$project" />
                @endforeach
            </div>
        </div>
    @endif

    <!-- Deleted Projects (Only visible to owner) -->
    @if($this->deletedProjects->count() > 0)
        <div>
            <h2 class="text-2xl font-bold mb-4">Deleted Projects</h2>
            <p class="text-sm text-base-content/60 mb-4">
                These projects will be permanently deleted after 14 days. You can restore them before then.
            </p>
            <div class="space-y-4">
                @foreach($this->deletedProjects as $project)
                    <x-card class="!py-3 !px-5 opacity-60">
                        <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">
                            <!-- Project Info -->
                            <div class="flex gap-4 flex-grow">
                                <div class="flex-shrink-0">
                                    <img src="{{ $project->getLogoUrl() ?? '/images/default-project.png' }}"
                                        class="w-20 h-20 object-cover rounded-lg"
                                        alt="{{ $project->name }} Logo">
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h3 class="text-lg font-bold mb-1">
                                        {{ $project->pretty_name ?? $project->name ?? 'Unnamed Project' }}
                                    </h3>
                                    <p class="text-sm mb-2">
                                        by <span class="font-medium">{{ $project->owner->first() ? $project->owner->first()->name : 'Unknown' }}</span>
                                    </p>
                                    <p class="text-sm text-base-content/60">
                                        <x-icon name="lucide-trash-2" class="w-3 h-3 inline" />
                                        Deleted {{ $project->deleted_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            <!-- Restore Button -->
                            <div class="flex-shrink-0">
                                <x-button 
                                    wire:click="restoreProject({{ $project->id }})"
                                    icon="lucide-rotate-ccw"
                                    class="btn-success btn-sm"
                                    wire:confirm="Are you sure you want to restore this project?"
                                >
                                    Restore
                                </x-button>
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
        </div>
    @endif

    <!-- No Projects Message -->
    @if($this->activeProjects->count() === 0 && $this->deletedProjects->count() === 0)
        <x-card class="text-center py-12">
            <x-icon name="lucide-package" class="w-16 h-16 mx-auto mb-4" />
            <h3 class="text-lg font-medium mb-2">No projects yet</h3>
            <p class="text-base-content/60">{{ $user->name }} hasn't created or contributed to any projects.</p>
        </x-card>
    @endif
</div>

