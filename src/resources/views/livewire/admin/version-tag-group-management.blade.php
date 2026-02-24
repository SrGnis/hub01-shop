<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Version Tag Groups</h2>
        <x-button label="Create Version Tag Group" icon="lucide-plus" wire:click="createVersionTagGroup"
            class="btn-primary btn-sm" />
    </div>

    {{-- Version Tag Groups Table --}}
    <x-table :headers="[
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'slug', 'label' => 'Slug'],
        ['key' => 'project_types', 'label' => 'Project Types'],
        ['key' => 'tags_count', 'label' => 'Tags'],
        ['key' => 'actions', 'label' => 'Actions'],
    ]" :rows="$versionTagGroups" striped>
        @scope('cell_project_types', $group)
            <div class="flex flex-wrap gap-1">
                @forelse($group->projectTypes as $type)
                    <x-badge :value="$type->display_name" class="badge-sm badge-ghost" />
                @empty
                    <span class="text-gray-400 text-sm">None</span>
                @endforelse
            </div>
        @endscope

        @scope('cell_slug', $group)
            <span class="text-gray-500">{{ $group->slug }}</span>
        @endscope

        @scope('cell_tags_count', $group)
            {{ $group->tags->count() }}
        @endscope

        @scope('cell_actions', $group)
            <div class="flex gap-2">
                <x-button icon="lucide-pencil" wire:click="editVersionTagGroup({{ $group->id }})"
                    class="btn-sm btn-ghost" tooltip="Edit" />
                <x-button icon="lucide-trash-2" wire:click="confirmDeletion({{ $group->id }})"
                    class="btn-sm btn-ghost text-error" tooltip="Delete" />
            </div>
        @endscope
    </x-table>

    {{-- Create/Edit Modal --}}
    <x-modal wire:model="showModal"
        title="{{ $isEditingVersionTagGroup ? 'Edit Version Tag Group' : 'Create Version Tag Group' }}">
        <x-form wire:submit="saveVersionTagGroup">
            <x-input label="Name" wire:model.live.debounce.500ms="versionTagGroupName"
                hint="Group name (e.g., 'Game Versions', 'Modloaders')" required />

            <x-input label="Slug" wire:model.live.debounce.500ms="versionTagGroupSlug" spinner="versionTagName, versionTagGroupSlug" required />

            <x-input
                label="Display Priority"
                type="number"
                min="0"
                step="1"
                wire:model="versionTagGroupDisplayPriority"
                hint="Lower values are shown first"
                required
            />

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Associated Project Types</span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($projectTypes as $type)
                        <x-checkbox wire:model="versionTagGroupProjectTypes" :label="$type->display_name" :value="$type->id" />
                    @endforeach
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showModal', false)" />
                <x-button label="{{ $isEditingVersionTagGroup ? 'Update' : 'Create' }}" type="submit"
                    class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Delete Confirmation Modal --}}
    <x-modal wire:model="confirmingDeletion" title="Delete Version Tag Group">
        @if ($itemToDelete)
            @php
                $group = \App\Models\ProjectVersionTagGroup::find($itemToDelete);
            @endphp
            @if ($group)
                <p>Are you sure you want to delete <strong>{{ $group->name }}</strong>?</p>
                @if ($group->tags->count() > 0)
                    <p class="text-sm text-warning mt-2">This will also delete {{ $group->tags->count() }} version
                        tag(s) in this group.</p>
                @endif
            @endif
        @endif

        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('confirmingDeletion', false)" />
            <x-button label="Delete" wire:click="deleteItem" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>
