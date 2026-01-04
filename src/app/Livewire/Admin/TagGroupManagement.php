<?php

namespace App\Livewire\Admin;

use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use Livewire\Component;
use Mary\Traits\Toast;

class TagGroupManagement extends Component
{
    use Toast;

    public $tagGroupId = null;

    public $tagGroupName = '';

    public $tagGroupProjectTypes = [];

    public $isEditingTagGroup = false;

    // Modal state
    public $showModal = false;

    // Confirmation
    public $confirmingDeletion = false;

    public $itemToDelete = null;

    protected function rules()
    {
        return [
            'tagGroupName' => 'required|string|max:50',
        ];
    }

    public function createTagGroup()
    {
        $this->resetTagGroupForm();
        $this->isEditingTagGroup = false;
        $this->showModal = true;
    }

    public function editTagGroup($id)
    {
        $tagGroup = ProjectTagGroup::find($id);
        if (! $tagGroup) {
            $this->error('Tag group not found.');

            return;
        }

        $this->tagGroupId = $tagGroup->id;
        $this->tagGroupName = $tagGroup->name;
        $this->tagGroupProjectTypes = $tagGroup->projectTypes->pluck('id')->toArray();
        $this->isEditingTagGroup = true;
        $this->showModal = true;
    }

    public function saveTagGroup()
    {
        $this->validate();

        if ($this->isEditingTagGroup) {
            $tagGroup = ProjectTagGroup::find($this->tagGroupId);
            $tagGroup->name = $this->tagGroupName;
            $tagGroup->save();

            // Sync project types
            $tagGroup->projectTypes()->sync($this->tagGroupProjectTypes);

            $this->success('Tag group updated successfully.');
        } else {
            $tagGroup = ProjectTagGroup::create([
                'name' => $this->tagGroupName,
            ]);

            // Sync project types
            $tagGroup->projectTypes()->sync($this->tagGroupProjectTypes);

            $this->success('Tag group created successfully.');
        }

        $this->resetTagGroupForm();
        $this->showModal = false;
    }

    public function resetTagGroupForm()
    {
        $this->tagGroupId = null;
        $this->tagGroupName = '';
        $this->tagGroupProjectTypes = [];
        $this->isEditingTagGroup = false;
    }

    public function confirmDeletion($id)
    {
        $this->confirmingDeletion = true;
        $this->itemToDelete = $id;
    }

    public function deleteItem()
    {
        $item = ProjectTagGroup::find($this->itemToDelete);

        if ($item) {
            $item->delete();
            $this->success('Tag group deleted successfully.');
        }

        $this->confirmingDeletion = false;
        $this->itemToDelete = null;
    }

    public function render()
    {
        return view('livewire.admin.tag-group-management', [
            'tagGroups' => ProjectTagGroup::all(),
            'projectTypes' => ProjectType::all(),
        ]);
    }
}
