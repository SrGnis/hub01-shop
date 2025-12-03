<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Project Types</h2>
        <x-button label="Create Project Type" icon="lucide-plus" wire:click="createProjectType"
            class="btn-primary btn-sm" />
    </div>

    {{-- Project Types Table --}}
    <x-table :headers="[
        ['key' => 'value', 'label' => 'Value'],
        ['key' => 'display_name', 'label' => 'Display Name'],
        ['key' => 'icon', 'label' => 'Icon'],
        ['key' => 'actions', 'label' => 'Actions'],
    ]" :rows="$projectTypes" striped>
        @scope('cell_icon', $type)
            <div class="flex items-center gap-2">
                <x-icon :name="$type->icon" class="w-5 h-5" />
                <code class="text-xs">{{ $type->icon }}</code>
            </div>
        @endscope

        @scope('cell_actions', $type)
            <div class="flex gap-2">
                <x-button icon="lucide-pencil" wire:click="editProjectType({{ $type->id }})" class="btn-sm btn-ghost"
                    tooltip="Edit" />
                <x-button icon="lucide-trash-2" wire:click="confirmDeletion({{ $type->id }})"
                    class="btn-sm btn-ghost text-error" tooltip="Delete" />
            </div>
        @endscope
    </x-table>

    {{-- Create/Edit Modal --}}
    <x-modal wire:model="showModal" title="{{ $isEditingProjectType ? 'Edit Project Type' : 'Create Project Type' }}">
        <x-form wire:submit="saveProjectType">
            <x-input label="Value" wire:model="projectTypeValue" hint="Lowercase identifier (e.g., 'mod', 'modpack')"
                required />
            <x-input label="Display Name" wire:model="projectTypeDisplayName" hint="Human-readable name" required />
            <x-input label="Icon" wire:model="projectTypeIcon" hint="Lucide icon name (must start with 'lucide-')"
                required />

            <div class="text-sm text-gray-400 mt-2">
                Preview: <x-icon :name="$projectTypeIcon" class="w-5 h-5 inline" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showModal', false)" />
                <x-button label="{{ $isEditingProjectType ? 'Update' : 'Create' }}" type="submit"
                    class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Delete Confirmation Modal --}}
    <x-modal wire:model="confirmingDeletion" title="Delete Project Type">
        @if ($itemToDelete)
            @php
                $type = \App\Models\ProjectType::find($itemToDelete);
            @endphp
            @if ($type)
                <p>Are you sure you want to delete <strong>{{ $type->display_name }}</strong>?</p>
                <p class="text-sm text-gray-400 mt-2">This action cannot be undone.</p>
            @endif
        @endif

        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('confirmingDeletion', false)" />
            <x-button label="Delete" wire:click="deleteItem" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>
