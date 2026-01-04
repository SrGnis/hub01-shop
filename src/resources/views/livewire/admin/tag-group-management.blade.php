<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Tag Groups</h2>
        <x-button label="Create Tag Group" icon="lucide-plus" wire:click="createTagGroup" class="btn-primary btn-sm" />
    </div>

    {{-- Tag Groups Table --}}
    <x-table :headers="[
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'project_types', 'label' => 'Project Types'],
        ['key' => 'tags_count', 'label' => 'Tags'],
        ['key' => 'actions', 'label' => 'Actions'],
    ]" :rows="$tagGroups" striped>
        @scope('cell_project_types', $group)
            <div class="flex flex-wrap gap-1">
                @forelse($group->projectTypes as $type)
                    <x-badge :value="$type->display_name" class="badge-sm badge-ghost" />
                @empty
                    <span class="text-gray-400 text-sm">None</span>
                @endforelse
            </div>
        @endscope

        @scope('cell_tags_count', $group)
            {{ $group->tags->count() }}
        @endscope

        @scope('cell_actions', $group)
            <div class="flex gap-2">
                <x-button icon="lucide-pencil" wire:click="editTagGroup({{ $group->id }})" class="btn-sm btn-ghost"
                    tooltip="Edit" />
                <x-button icon="lucide-trash-2" wire:click="confirmDeletion({{ $group->id }})"
                    class="btn-sm btn-ghost text-error" tooltip="Delete" />
            </div>
        @endscope
    </x-table>

    {{-- Create/Edit Modal --}}
    <x-modal wire:model="showModal" title="{{ $isEditingTagGroup ? 'Edit Tag Group' : 'Create Tag Group' }}">
        <x-form wire:submit="saveTagGroup">
            <x-input label="Name" wire:model="tagGroupName" hint="Group name (e.g., 'Categories', 'Features')"
                required />

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Associated Project Types</span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($projectTypes as $type)
                        <x-checkbox wire:model="tagGroupProjectTypes" :label="$type->display_name" :value="$type->id" />
                    @endforeach
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showModal', false)" />
                <x-button label="{{ $isEditingTagGroup ? 'Update' : 'Create' }}" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Delete Confirmation Modal --}}
    <x-modal wire:model="confirmingDeletion" title="Delete Tag Group">
        @if ($itemToDelete)
            @php
                $group = \App\Models\ProjectTagGroup::find($itemToDelete);
            @endphp
            @if ($group)
                <p>Are you sure you want to delete <strong>{{ $group->name }}</strong>?</p>
                @if ($group->tags->count() > 0)
                    <p class="text-sm text-warning mt-2">This will also delete {{ $group->tags->count() }} tag(s) in
                        this group.</p>
                @endif
            @endif
        @endif

        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('confirmingDeletion', false)" />
            <x-button label="Delete" wire:click="deleteItem" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>
