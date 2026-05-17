<x-platform.shell>

    <x-slot:header>
        <x-header
            title="Projects"
            icon="package"
            icon-classes="w-6 h-6"
            subtitle="Browse the projects you are member of."
            class="!mb-0"
            >
            <x-slot:actions class="w-full justify-end md:w-auto">
                <div x-data="{ showFilters: false }" class="w-full md:w-auto">
                    <div class="md:hidden mb-3 w-full flex justify-end">
                        <x-button
                            type="button"
                            class="btn-ghost btn-sm"
                            icon="lucide-sliders-horizontal"
                            label="Filters"
                            @click="showFilters = !showFilters"
                            ::aria-expanded="showFilters.toString()"
                        />
                    </div>

                    <div
                        class="grid grid-cols-1 md:grid-cols-5 gap-4"
                        x-show="showFilters || window.matchMedia('(min-width: 768px)').matches"
                        x-transition
                    >
                        <div class="md:col-span-2">
                            <x-input
                                label="Search"
                                placeholder="Search by name or slug..."
                                icon="lucide-search"
                                wire:model.live.debounce.300ms="search"
                                clearable
                            />
                        </div>

                        <x-select
                            label="Type"
                            wire:model.live="type"
                            :options="$this->typeOptions"
                        />

                        <x-select
                            label="Status"
                            wire:model.live="status"
                            :options="[
                                ['id' => 'all', 'name' => 'All statuses'],
                                ['id' => 'active', 'name' => 'Active'],
                                ['id' => 'inactive', 'name' => 'Inactive'],
                            ]"
                        />

                        <x-select
                            label="Per page"
                            wire:model.live="perPage"
                            :options="[
                                ['id' => 10, 'name' => '10'],
                                ['id' => 25, 'name' => '25'],
                                ['id' => 50, 'name' => '50'],
                            ]"
                        />
                    </div>
                </div>
            </x-slot:actions>
        </x-header>
    </x-slot:header>

    <x-slot:left>
        <x-platform.section-nav current="projects" />
    </x-slot:left>

    @php
        $headers = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'slug', 'label' => 'Slug', 'class' => 'hidden lg:table-cell'],
            ['key' => 'type', 'label' => 'Type', 'class' => 'hidden lg:table-cell'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'hidden lg:table-cell'],
        ];
    @endphp

    <x-card aria-live="polite">

        <div class="flex w-full justify-end">
            <x-button
                icon="plus"
                label="Publish"
                class="btn-primary"
                onclick="Livewire.dispatch('open-project-create-modal')"
                aria-label="Create a new project"
            />
        </div>

        @if ($this->projects->count() === 0)
            <div class="text-center py-12">
                <x-icon name="lucide-package-x" class="w-14 h-14 mx-auto mb-4 text-base-content/40" />
                <h3 class="text-lg font-medium mb-2">No projects found</h3>
                <p class="text-sm text-base-content/60">Try adjusting filters or create a new project.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <x-table :headers="$headers" :rows="$this->projects" :sort-by="$sortBy" class="table-zebra" aria-label="Projects list">
                    @scope('cell_name', $project)
                        <div class="font-medium">
                            <a href="{{ route('project.show', ['projectType' => $project->projectType->value, 'project' => $project]) }}" class="inline-flex items-center gap-2">
                                <img
                                    src="{{ $project->getLogoUrl() }}"
                                    alt="{{ $project->pretty_name }} logo"
                                    class="w-6 h-6 rounded object-cover"
                                />
                                <span>{{ $project->pretty_name }}</span>
                            </a>
                            <div class="lg:hidden text-xs text-base-content/70 flex flex-wrap items-center gap-2 mt-2">
                                <code>{{ $project->slug }}</code>
                                <x-badge :value="$project->projectType?->display_name ?? ucfirst((string) $project->projectType?->value)" class="badge-xs badge-ghost" />
                                <x-badge
                                    :value="ucfirst((string) $project->status)"
                                    class="badge-xs {{ (string) $project->status === 'active' ? 'badge-success' : 'badge-warning' }}"
                                />
                            </div>
                        </div>
                    @endscope

                    @scope('cell_slug', $project)
                        <code class="text-xs">{{ $project->slug }}</code>
                    @endscope

                    @scope('cell_type', $project)
                        <x-badge :value="$project->projectType?->display_name ?? ucfirst((string) $project->projectType?->value)" class="badge-sm badge-primary" />
                    @endscope

                    @scope('cell_status', $project)
                        <x-badge
                            :value="ucfirst((string) $project->status)"
                            class="badge-sm {{ (string) $project->status === 'active' ? 'badge-success' : 'badge-warning' }}"
                        />
                    @endscope

                    @scope('actions', $project)
                        <div class="flex items-center justify-end gap-2">
                            @if ($project->projectType?->value)
                                <x-button
                                    icon="settings"
                                    class="btn-ghost btn-sm"
                                    link="{{ route('project.edit', ['projectType' => $project->projectType->value, 'project' => $project]) }}"
                                    aria-label="Project {{ $project->name }} settings"
                                />
                            @endif
                        </div>
                    @endscope
                </x-table>
            </div>

            <div class="mt-4">
                {{ $this->projects->links() }}
            </div>
        @endif
    </x-card>
</x-platform.shell>
