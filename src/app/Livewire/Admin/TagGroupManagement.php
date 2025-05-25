<?php

namespace App\Livewire\Admin;

use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use Livewire\Component;

class TagGroupManagement extends Component
{
    public $tagGroupId = null;

    public $tagGroupName = '';

    public $tagGroupProjectTypes = [];

    public $isEditingTagGroup = false;

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
    }

    public function editTagGroup($id)
    {
        $tagGroup = ProjectTagGroup::find($id);
        if (! $tagGroup) {
            session()->flash('error', 'Tag group not found.');

            return;
        }

        $this->tagGroupId = $tagGroup->id;
        $this->tagGroupName = $tagGroup->name;
        $this->tagGroupProjectTypes = $tagGroup->projectTypes->pluck('id')->toArray();
        $this->isEditingTagGroup = true;
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

            session()->flash('message', 'Tag group updated successfully.');
        } else {
            $tagGroup = ProjectTagGroup::create([
                'name' => $this->tagGroupName,
            ]);

            // Sync project types
            $tagGroup->projectTypes()->sync($this->tagGroupProjectTypes);

            session()->flash('message', 'Tag group created successfully.');
        }

        $this->resetTagGroupForm();
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
            session()->flash('message', 'Tag group deleted successfully.');
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
