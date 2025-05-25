<?php

namespace App\Livewire\Admin;

use App\Models\ProjectType;
use App\Models\ProjectVersionTagGroup;
use Livewire\Component;

class VersionTagGroupManagement extends Component
{
    public $versionTagGroupId = null;

    public $versionTagGroupName = '';

    public $versionTagGroupProjectTypes = [];

    public $isEditingVersionTagGroup = false;

    // Confirmation
    public $confirmingDeletion = false;

    public $itemToDelete = null;

    protected function rules()
    {
        return [
            'versionTagGroupName' => 'required|string|max:50',
        ];
    }

    public function createVersionTagGroup()
    {
        $this->resetVersionTagGroupForm();
        $this->isEditingVersionTagGroup = false;
    }

    public function editVersionTagGroup($id)
    {
        $versionTagGroup = ProjectVersionTagGroup::find($id);
        if (! $versionTagGroup) {
            session()->flash('error', 'Version tag group not found.');

            return;
        }

        $this->versionTagGroupId = $versionTagGroup->id;
        $this->versionTagGroupName = $versionTagGroup->name;
        $this->versionTagGroupProjectTypes = $versionTagGroup->projectTypes->pluck('id')->toArray();
        $this->isEditingVersionTagGroup = true;
    }

    public function saveVersionTagGroup()
    {
        $this->validate();

        if ($this->isEditingVersionTagGroup) {
            $versionTagGroup = ProjectVersionTagGroup::find($this->versionTagGroupId);
            $versionTagGroup->name = $this->versionTagGroupName;
            $versionTagGroup->save();

            // Sync project types
            $versionTagGroup->projectTypes()->sync($this->versionTagGroupProjectTypes);

            session()->flash('message', 'Version tag group updated successfully.');
        } else {
            $versionTagGroup = ProjectVersionTagGroup::create([
                'name' => $this->versionTagGroupName,
            ]);

            // Sync project types
            $versionTagGroup->projectTypes()->sync($this->versionTagGroupProjectTypes);

            session()->flash('message', 'Version tag group created successfully.');
        }

        $this->resetVersionTagGroupForm();
    }

    public function resetVersionTagGroupForm()
    {
        $this->versionTagGroupId = null;
        $this->versionTagGroupName = '';
        $this->versionTagGroupProjectTypes = [];
        $this->isEditingVersionTagGroup = false;
    }

    public function confirmDeletion($id)
    {
        $this->confirmingDeletion = true;
        $this->itemToDelete = $id;
    }

    public function deleteItem()
    {
        $item = ProjectVersionTagGroup::find($this->itemToDelete);

        if ($item) {
            $item->delete();
            session()->flash('message', 'Version tag group deleted successfully.');
        }

        $this->confirmingDeletion = false;
        $this->itemToDelete = null;
    }

    public function render()
    {
        return view('livewire.admin.version-tag-group-management', [
            'versionTagGroups' => ProjectVersionTagGroup::all(),
            'projectTypes' => ProjectType::all(),
        ]);
    }
}
