<x-card class="space-y-6" x-on:input="markDirty()" x-on:change="markDirty()">
    {{-- Name --}}
    <x-input label="Name" wire:model="name" placeholder="Project name" required />

    {{-- Slug --}}
    <div>
        <div class="flex justify-between items-center mt-2">
            <label class="text-sm font-medium">
                URL Slug
                <span class="text-error">*</span>
                <span wire:loading wire:target="name, slug" class="loading loading-spinner w-4 h-4"></span>
            </label>
            <x-button spinner type="button" wire:click="generateSlug" x-on:click="markDirty()" label="Generate from Name" class="btn-sm" />
        </div>
        <x-input wire:model.live.debounce.500ms="slug" placeholder="project-slug"
            prefix="{{ route('dummy.project.show', ['projectType' => $projectType]) }}/"
            required />
        <p class="text-warning text-xs mt-1">Warning: Changing the slug will change all URLs.</p>
    </div>

    {{-- Summary --}}
    <x-textarea label="Summary" wire:model="summary" placeholder="Brief project description" rows="2"
        maxlength="125" hint="Max 125 characters" required />

    {{-- Project Logo --}}
    <x-file wire:model="logo" label="Project Logo" accept="image/*" crop-after-change
        hint="Recommended: Square image, 512x512px"
        :is-image=1
        :image-url="$project->logo_path ? \Illuminate\Support\Facades\Storage::url($project->logo_path) : null"
        :placeholder-url="asset('images/placeholder.png')"
        remove-method="removeLogo">
    </x-file>

    {{-- Status --}}
    <div>
        <x-custom-toggle hint="Set a project as inactive to inform users that it is no longer maintained."
            wire:model="status" on-value="active" off-value="inactive" on-label="Active"
            off-label="Inactive" />
    </div>
</x-card>
