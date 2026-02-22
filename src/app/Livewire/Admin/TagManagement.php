<?php

namespace App\Livewire\Admin;

use App\Models\ProjectTag;
use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use Livewire\Component;
use Mary\Traits\Toast;

class TagManagement extends Component
{
    use Toast;

    public $tagId = null;

    public $projectTagName = '';

    public $projectTagSlug = '';

    public $tagIcon = 'lucide-tag';

    public ?int $tagGroupId = null;

    public ?int $tagParentId = null;

    public int $tagDisplayPriority = 0;

    public $tagProjectTypes = [];

    public $isEditingTag = false;

    // Modal state
    public $showModal = false;

    // Confirmation
    public $confirmingDeletion = false;

    public $itemToDelete = null;

    // Expanded rows for sub-tags (uses row->id by default)
    public array $expanded = [];

    protected function rules()
    {
        return [
            'projectTagName' => 'required|string|max:50',
            'projectTagSlug' => 'required|string|max:50|unique:project_tag,slug,' . $this->tagId,
            'tagIcon' => 'required|string|max:50|starts_with:lucide-',
            'tagParentId' => 'nullable|exists:project_tag,id',
            'tagDisplayPriority' => 'required|integer|min:0',
        ];
    }

    protected function messages()
    {
        return [
            'tagIcon.starts_with' => 'The icon must be a valid Lucide icon (starts with "lucide-").',
            'tagParentId.exists' => 'The selected parent tag does not exist.',
        ];
    }

    public function updatedProjectTagName(){
        $this->projectTagSlug = ProjectTag::createSlug($this->projectTagName);

        $this->resetValidation('projectTagSlug');
        $this->validateOnly('projectTagSlug');
    }

    public function updatedProjectTagSlug(){
        $this->resetValidation('projectTagSlug');
        $this->validateOnly('projectTagSlug');
    }

    public function createTag()
    {
        $this->resetTagForm();
        $this->isEditingTag = false;
        $this->showModal = true;
    }

    public function editTag($id)
    {
        $tag = ProjectTag::find($id);
        if (! $tag) {
            $this->error('Tag not found.');

            return;
        }

        $this->tagId = $tag->id;
        $this->projectTagName = $tag->name;
        $this->projectTagSlug = $tag->slug;
        $this->tagIcon = $tag->icon;
        $this->tagGroupId = $tag->project_tag_group_id;
        $this->tagParentId = $tag->parent_id;
        $this->tagDisplayPriority = $tag->display_priority;
        $this->tagProjectTypes = $tag->projectTypes->pluck('id')->toArray();
        $this->isEditingTag = true;
        $this->showModal = true;
    }

    public function saveTag()
    {
        $this->validate();

        if ($this->isEditingTag) {
            $tag = ProjectTag::find($this->tagId);
            $tag->name = $this->projectTagName;
            $tag->slug = $this->projectTagSlug;
            $tag->icon = $this->tagIcon;
            $tag->project_tag_group_id = $this->tagGroupId;
            $tag->parent_id = $this->tagParentId;
            $tag->display_priority = $this->tagDisplayPriority;
            $tag->save();

            // Sync project types
            $tag->projectTypes()->sync($this->tagProjectTypes);

            $this->success('Tag updated successfully.');
        } else {
            $tag = ProjectTag::create([
                'name' => $this->projectTagName,
                'slug' => $this->projectTagSlug,
                'icon' => $this->tagIcon,
                'project_tag_group_id' => $this->tagGroupId,
                'parent_id' => $this->tagParentId,
                'display_priority' => $this->tagDisplayPriority,
            ]);

            // Sync project types
            $tag->projectTypes()->sync($this->tagProjectTypes);

            $this->success('Tag created successfully.');
        }

        $this->resetTagForm();
        $this->showModal = false;
    }

    public function resetTagForm()
    {
        $this->tagId = null;
        $this->projectTagName = '';
        $this->projectTagSlug = '';
        $this->tagIcon = 'lucide-tag';
        $this->tagGroupId = null;
        $this->tagParentId = null;
        $this->tagDisplayPriority = 0;
        $this->tagProjectTypes = [];
        $this->isEditingTag = false;
    }

    public function confirmDeletion($id)
    {
        $this->confirmingDeletion = true;
        $this->itemToDelete = $id;
    }

    public function deleteItem()
    {
        $item = ProjectTag::find($this->itemToDelete);

        if ($item) {
            $item->delete();
            $this->success('Tag deleted successfully.');
        }

        $this->confirmingDeletion = false;
        $this->itemToDelete = null;
    }

    public function render()
    {
        // Get main tags with their sub-tag counts
        $mainTags = ProjectTag::onlyMain()
            ->with('tagGroup')
            ->withCount('children')
            ->get();

        return view('livewire.admin.tag-management', [
            'mainTags' => $mainTags,
            'tagGroups' => ProjectTagGroup::all(),
            'projectTypes' => ProjectType::all(),
        ]);
    }
}
