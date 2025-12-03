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

    public $tagIcon = 'lucide-tag';

    public $tagGroupId = null;

    public $tagProjectTypes = [];

    public $isEditingTag = false;

    // Modal state
    public $showModal = false;

    // Confirmation
    public $confirmingDeletion = false;

    public $itemToDelete = null;

    protected function rules()
    {
        return [
            'projectTagName' => 'required|string|max:50',
            'tagIcon' => 'required|string|max:50|starts_with:lucide-',
        ];
    }

    protected function messages()
    {
        return [
            'tagIcon.starts_with' => 'The icon must be a valid Lucide icon (starts with "lucide-").',
        ];
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
        $this->tagIcon = $tag->icon;
        $this->tagGroupId = $tag->project_tag_group_id;
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
            $tag->icon = $this->tagIcon;
            $tag->project_tag_group_id = $this->tagGroupId;
            $tag->save();

            // Sync project types
            $tag->projectTypes()->sync($this->tagProjectTypes);

            $this->success('Tag updated successfully.');
        } else {
            $tag = ProjectTag::create([
                'name' => $this->projectTagName,
                'icon' => $this->tagIcon,
                'project_tag_group_id' => $this->tagGroupId,
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
        $this->tagIcon = 'lucide-tag';
        $this->tagGroupId = null;
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
        return view('livewire.admin.tag-management', [
            'tags' => ProjectTag::with('tagGroup')->get(),
            'tagGroups' => ProjectTagGroup::all(),
            'projectTypes' => ProjectType::all(),
        ]);
    }
}
