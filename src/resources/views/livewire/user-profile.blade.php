<div class="container mx-auto px-4 py-8 max-w-7xl">
    <livewire:report-abuse />

    <!-- User Profile Header -->
    <x-card class="mb-6">
        <div class="flex flex-col-reverse md:flex-row items-end md:items-center">
            <!-- User Avatar with Info -->
            <div class="w-full md:max-w-3xl">
                <x-avatar placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}"
                    placeholder-text-class="text-3xl font-bold" placeholder-bg-class="bg-primary text-primary-content"
                    class="!w-24" image="{{ $user->getAvatarUrl() }}">
                    <x-slot:title class="text-3xl !font-bold pl-2">
                        {{ $user->name }}
                    </x-slot:title>

                    <x-slot:subtitle class="grid gap-2 mt-2 pl-2">
                        @if ($user->bio)
                            <p class="text-sm text-base-content/80">{{ $user->bio }}</p>
                        @endif

                        <div class="flex flex-wrap gap-3 text-xs">
                            <x-icon name="lucide-calendar"
                                label="Member since {{ $user->created_at->format('F Y') }}" />
                            <x-icon name="lucide-package"
                                label="{{ $this->ownedProjectsCount }} {{ Str::plural('project', $this->ownedProjectsCount) }} owned" />
                            <x-icon name="lucide-download"
                                label="{{ number_format($this->aggregateDownloads) }} {{ Str::plural('download', $this->aggregateDownloads) }}" />
                        </div>
                    </x-slot:subtitle>
                </x-avatar>
            </div>

            <!-- User Actions -->
            <div class="flex-grow">
                <div class="flex justify-end">
                    @auth
                        @if (auth()->id() === $user->id)
                            <x-button link="{{ route('user.profile.edit') }}" icon="lucide-pencil" class="btn-primary">
                                Edit Profile
                            </x-button>
                        @endif
                    @endauth
                    <x-dropdown right>
                        <x-slot:trigger>
                            <x-button icon="ellipsis" class="btn-ghost" />
                        </x-slot:trigger>

                        <x-menu-item
                            title="Report"
                            class="text-error"
                            icon="flag"
                            @click="$dispatch('open-report-modal', { itemId: {{ $user->id }}, itemType: 'App\\\\Models\\\\User', itemName: '{{ addslashes($user->name) }}' })"
                        />
                    </x-dropdown>
                </div>
            </div>
        </div>
    </x-card>

    <x-tabs wire:model="activeTab" class="mb-6">
        <x-tab name="projects" label="Projects" icon="lucide-package">
            <div class="space-y-6 pt-4">
                @if ($this->activeProjects->count() > 0)
                    <div class="space-y-4">
                        @foreach ($this->activeProjects as $project)
                            <x-project-card
                                :project="$project"
                                action-favorite="toggleFavorite"
                                action-add-collection="openAddToCollectionModal"
                            />
                        @endforeach
                    </div>
                @endif

                <!-- Deleted Projects (Only visible to owner) -->
                @if ($this->deletedProjects->count() > 0)
                    <div>
                        <h2 class="text-2xl font-bold mb-4">Deleted Projects</h2>
                        <p class="text-sm text-base-content/60 mb-4">
                            These projects will be permanently deleted after 14 days.
                        </p>
                        <div class="space-y-4">
                            @foreach ($this->deletedProjects as $project)
                                <x-card class="!py-3 !px-5 opacity-60">
                                    <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">
                                        <div class="flex gap-4 flex-grow">
                                            <div class="flex-shrink-0">
                                                <img src="{{ $project->getLogoUrl() ?? '/images/default-project.png' }}"
                                                    class="w-20 h-20 object-cover rounded-lg" alt="{{ $project->name }} Logo">
                                            </div>
                                            <div class="flex-grow min-w-0">
                                                <h3 class="text-lg font-bold mb-1">
                                                    {{ $project->pretty_name ?? ($project->name ?? 'Unnamed Project') }}
                                                </h3>
                                                <p class="text-sm mb-2">
                                                    by <span
                                                        class="font-medium">{{ $project->owner->first() ? $project->owner->first()->name : 'Unknown' }}</span>
                                                </p>
                                                <p class="text-sm text-base-content/60">
                                                    <x-icon name="lucide-trash-2" class="w-3 h-3 inline" />
                                                    Deleted {{ $project->deleted_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex-shrink-0">
                                            <x-button wire:click="restoreProject({{ $project->id }})" icon="lucide-rotate-ccw"
                                                class="btn-success btn-sm"
                                                wire:confirm="Are you sure you want to restore this project?">
                                                Restore
                                            </x-button>
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->activeProjects->count() === 0 && $this->deletedProjects->count() === 0)
                    <x-card class="text-center py-12">
                        <x-icon name="lucide-package" class="w-16 h-16 mx-auto mb-4" />
                        <h3 class="text-lg font-medium mb-2">No projects yet</h3>
                        <p class="text-base-content/60">{{ $user->name }} hasn't created or contributed to any projects.</p>
                    </x-card>
                @endif
            </div>
        </x-tab>

        <x-tab name="collections" label="Collections" icon="lucide-folder-open">
            <div class="space-y-4 pt-4">
                @if (auth()->id() === $user->id && $this->favoritesCollection)
                    <x-card class="border border-base-300">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold flex items-center gap-2">
                                    <x-icon name="lucide-heart" class="w-4 h-4 text-error" />
                                    Favorites
                                    <x-badge value="System" class="badge-neutral badge-sm" />
                                </h3>
                                <p class="text-sm text-base-content/70">Private system collection.</p>
                            </div>
                            <x-badge value="{{ $this->favoritesCollection->entries_count }} items" class="badge-primary" />
                        </div>
                    </x-card>
                @endif

                @forelse ($this->visibleCollections as $collection)
                    <x-card>
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-2 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('collection.show', $collection) }}" class="text-lg font-semibold text-primary hover:text-primary-focus">
                                        {{ $collection->name }}
                                    </a>
                                    <x-badge value="{{ ucfirst($collection->visibility->value) }}" class="badge-outline badge-sm" />
                                    <x-badge value="{{ $collection->entries->count() }} items" class="badge-ghost badge-sm" />
                                </div>
                                @if ($collection->description)
                                    <p class="text-sm text-base-content/70 line-clamp-2">{{ $collection->description }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                <x-button icon="lucide-eye" class="btn-ghost btn-sm" link="{{ route('collection.show', $collection) }}" />
                                @if (auth()->id() === $user->id)
                                    <x-button icon="lucide-pencil" class="btn-ghost btn-sm" link="{{ route('collection.edit', $collection) }}" />
                                    <x-button icon="lucide-trash-2" class="btn-ghost btn-sm text-error"
                                        wire:click="deleteCollection('{{ $collection->uid }}')"
                                        wire:confirm="Delete this collection?" />
                                @endif
                            </div>
                        </div>
                    </x-card>
                @empty
                    <x-card class="text-center py-12">
                        <x-icon name="lucide-folder-open" class="w-16 h-16 mx-auto mb-4" />
                        <h3 class="text-lg font-medium mb-2">No collections yet</h3>
                        <p class="text-base-content/60">No collections are visible for this profile.</p>
                    </x-card>
                @endforelse
            </div>
        </x-tab>
    </x-tabs>

    <x-project-collection-modal
        wire:model="showCollectionModal"
        :target-project-name="$collectionTargetProjectName"
        :available-collections="$this->availableCollections"
    />
</div>
