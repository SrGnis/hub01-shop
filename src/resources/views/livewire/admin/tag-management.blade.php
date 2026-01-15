<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Project Tags</h2>
        <x-button label="Create Tag" icon="lucide-plus" wire:click="createTag" class="btn-primary btn-sm" />
    </div>

    {{-- Tags Table with Hierarchical Display --}}
    <x-table :headers="[
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'icon', 'label' => 'Icon'],
        ['key' => 'group', 'label' => 'Group'],
        ['key' => 'sub_tags_count', 'label' => 'Sub-tags'],
        ['key' => 'project_types', 'label' => 'Project Types'],
        ['key' => 'actions', 'label' => 'Actions'],
    ]" :rows="$mainTags" wire:model="expanded" expandable expandable-condition="has_sub_tags" striped>
        @scope('cell_name', $tag)
            <div class="flex items-center gap-2">
                <x-icon :name="$tag->icon" class="w-4 h-4" />
                <span>{{ $tag->name }}</span>
                @if($tag->parent_id)
                    <x-badge value="Sub-tag" class="badge-xs badge-info" />
                @endif
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

        @scope('cell_sub_tags_count', $tag)
            @if($tag->children_count > 0)
                <x-badge :value="$tag->children_count . ' sub-tags'" class="badge-sm badge-outline" />
            @else
                <span class="text-gray-400 text-sm">-</span>
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
                <x-button icon="lucide-pencil" wire:click="editTag({{ $tag->id }})" class="btn-sm btn-ghost"
                    tooltip="Edit" />
                <x-button icon="lucide-trash-2" wire:click="confirmDeletion({{ $tag->id }})"
                    class="btn-sm btn-ghost text-error" tooltip="Delete" />
            </div>
        @endscope

        {{-- Sub-tags expansion (uses $row->id by default) --}}
        @scope('expansion', $tag)
            <div class="bg-base-200/50 p-4">
                <div class="text-xs font-medium text-gray-500 mb-2">Sub-tags:</div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($tag->children as $subTag)
                        <div class="flex items-center gap-2 bg-base-100 p-2 rounded border border-base-300">
                            <x-icon :name="$subTag->icon" class="w-3 h-3" />
                            <span class="text-sm">{{ $subTag->name }}</span>
                            <div class="ml-auto flex gap-1">
                                <x-button icon="lucide-pencil" wire:click="editTag({{ $subTag->id }})" class="btn-ghost btn-xs" />
                                <x-button icon="lucide-trash-2" wire:click="confirmDeletion({{ $subTag->id }})" class="btn-ghost btn-xs text-error" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endscope
    </x-table>

    {{-- Create/Edit Modal --}}
    <x-modal wire:model="showModal" title="{{ $isEditingTag ? 'Edit Tag' : 'Create Tag' }}">
        <x-form wire:submit="saveTag">
            <x-input label="Name" wire:model="projectTagName" hint="Tag name (e.g., 'Magic', 'Technology')"
                required />
            <x-input label="Icon" wire:model="tagIcon" hint="Lucide icon name (must start with 'lucide-')" required />

            <div class="text-sm text-gray-400 mt-2">
                Preview: <x-icon :name="$tagIcon" class="w-5 h-5 inline" />
            </div>

            <x-select label="Tag Group" wire:model="tagGroupId" :options="$tagGroups"
                placeholder="Select group (optional)" />

            <x-select
                label="Parent Tag (Main Tag)"
                wire:model="tagParentId"
                :options="$mainTags"
                placeholder="None (this is a main tag)"
                hint="Select a parent tag to make this a sub-tag"
            />

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Associated Project Types</span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($projectTypes as $type)
                        <x-checkbox wire:model="tagProjectTypes" :label="$type->display_name" :value="$type->id" />
                    @endforeach
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showModal', false)" />
                <x-button label="{{ $isEditingTag ? 'Update' : 'Create' }}" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Delete Confirmation Modal --}}
    <x-modal wire:model="confirmingDeletion" title="Delete Tag">
        @if ($itemToDelete)
            @php
                $tag = \App\Models\ProjectTag::find($itemToDelete);
            @endphp
            @if ($tag)
                <p>Are you sure you want to delete <strong>{{ $tag->name }}</strong>?</p>
                @if($tag->children_count > 0)
                    <p class="text-sm text-warning mt-2">Warning: This tag has {{ $tag->children_count }} sub-tag(s) that will also be affected.</p>
                @endif
                <p class="text-sm text-gray-400 mt-2">This action cannot be undone.</p>
            @endif
        @endif

        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('confirmingDeletion', false)" />
            <x-button label="Delete" wire:click="deleteItem" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>
