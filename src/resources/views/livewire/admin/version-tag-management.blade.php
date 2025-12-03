<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Version Tags</h2>
        <x-button label="Create Version Tag" icon="lucide-plus" wire:click="createVersionTag" class="btn-primary btn-sm" />
    </div>

    {{-- Version Tags Table --}}
    <x-table :headers="[
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'icon', 'label' => 'Icon'],
        ['key' => 'group', 'label' => 'Group'],
        ['key' => 'project_types', 'label' => 'Project Types'],
        ['key' => 'actions', 'label' => 'Actions'],
    ]" :rows="$versionTags" striped>
        @scope('cell_name', $tag)
            <div class="flex items-center gap-2">
                <x-icon :name="$tag->icon" class="w-4 h-4" />
                <span>{{ $tag->name }}</span>
            </div>
        @endscope

        @scope('cell_icon', $tag)
            <code class="text-xs">{{ $tag->icon }}</code>
        @endscope

        @scope('cell_group', $tag)
            @if ($tag->tagGroup)
                <x-badge :value="$tag->tagGroup->name" class="badge-sm badge-ghost" />
            @else
                <span class="text-gray-400 text-sm">None</span>
            @endif
        @endscope

        @scope('cell_project_types', $tag)
            <div class="flex flex-wrap gap-1">
                @forelse($tag->projectTypes as $type)
                    <x-badge :value="$type->display_name" class="badge-sm badge-ghost" />
                @empty
                    <span class="text-gray-400 text-sm">None</span>
                @endforelse
            </div>
        @endscope

        @scope('cell_actions', $tag)
            <div class="flex gap-2">
                <x-button icon="lucide-pencil" wire:click="editVersionTag({{ $tag->id }})" class="btn-sm btn-ghost"
                    tooltip="Edit" />
                <x-button icon="lucide-trash-2" wire:click="confirmDeletion({{ $tag->id }})"
                    class="btn-sm btn-ghost text-error" tooltip="Delete" />
            </div>
        @endscope
    </x-table>

    {{-- Create/Edit Modal --}}
    <x-modal wire:model="showModal" title="{{ $isEditingVersionTag ? 'Edit Version Tag' : 'Create Version Tag' }}">
        <x-form wire:submit="saveVersionTag">
            <x-input label="Name" wire:model="versionTagName" hint="Tag name (e.g., '1.20.1', 'Forge')" required />
            <x-input label="Icon" wire:model="versionTagIcon" hint="Lucide icon name (must start with 'lucide-')"
                required />

            <div class="text-sm text-gray-400 mt-2">
                Preview: <x-icon :name="$versionTagIcon" class="w-5 h-5 inline" />
            </div>

            <x-select label="Version Tag Group" wire:model="versionTagGroupId" :options="$versionTagGroups->map(fn($g) => ['id' => $g->id, 'name' => $g->name])"
                placeholder="Select group (optional)" />

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Associated Project Types</span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($projectTypes as $type)
                        <x-checkbox wire:model="versionTagProjectTypes" :label="$type->display_name" :value="$type->id" />
                    @endforeach
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showModal', false)" />
                <x-button label="{{ $isEditingVersionTag ? 'Update' : 'Create' }}" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Delete Confirmation Modal --}}
    <x-modal wire:model="confirmingDeletion" title="Delete Version Tag">
        @if ($itemToDelete)
            @php
                $tag = \App\Models\ProjectVersionTag::find($itemToDelete);
            @endphp
            @if ($tag)
                <p>Are you sure you want to delete <strong>{{ $tag->name }}</strong>?</p>
                <p class="text-sm text-gray-400 mt-2">This action cannot be undone.</p>
            @endif
        @endif

        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('confirmingDeletion', false)" />
            <x-button label="Delete" wire:click="deleteItem" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>
