<x-modal wire:model="isOpen" title="Create New Project" persistent size="lg">
    <form wire:submit="create" class="space-y-4">
        <!-- Project Type -->
        <x-select
            label="Project Type"
            wire:model="selectedType"
            :options="collect($projectTypes)->map(fn($type) => [
                'id' => $type['value'],
                'name' => $type['display_name'],
                'icon' => $type['icon'] ?? null
            ])->toArray()"
            required
            placeholder="Select project type..."
        />

        <!-- Name -->
        <x-input
            label="Name"
            wire:model.live.debounce.500ms="name"
            placeholder="Project name"
            required
        />

        <!-- Slug -->
        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="text-sm font-medium">
                    URL Slug
                    <span class="text-error">*</span>
                    <span wire:loading wire:target="name, slug" class="loading loading-spinner w-4 h-4"></span>
                </label>
                <x-button spinner type="button" wire:click="generateSlug" label="Generate from Name" class="btn-sm" />
            </div>
            <x-input
                wire:model.live.debounce.500ms="slug"
                placeholder="project-slug"
                required
            />
            <p class="text-gray-400 text-xs mt-1">The URL slug will be used in links to your project.</p>
            @error('slug')
                <span class="text-error text-sm">{{ $message }}</span>
            @enderror
        </div>

        <!-- Summary -->
        <x-textarea
            label="Summary"
            wire:model="summary"
            placeholder="Brief project description"
            rows="2"
            maxlength="125"
            hint="Max 125 characters"
            required
        />

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6">
            <x-button type="button" wire:click="close" label="Cancel" class="btn-ghost" />
            <x-button type="submit" spinner label="Create Project" class="btn-primary" />
        </div>
    </form>
</x-modal>
