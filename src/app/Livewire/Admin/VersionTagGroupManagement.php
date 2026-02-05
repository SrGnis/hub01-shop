<?php

namespace App\Livewire\Admin;

use App\Models\ProjectType;
use App\Models\ProjectVersionTagGroup;
use Livewire\Component;
use Mary\Traits\Toast;

class VersionTagGroupManagement extends Component
{
    use Toast;

    public $versionTagGroupId = null;

    public $versionTagGroupName = '';

    public $versionTagGroupSlug = '';

    public $versionTagGroupProjectTypes = [];

    public $isEditingVersionTagGroup = false;

    // Modal state
    public $showModal = false;

    // Confirmation
    public $confirmingDeletion = false;

    public $itemToDelete = null;

    protected function rules()
    {
        return [
            'versionTagGroupName' => 'required|string|max:50',
            'versionTagGroupSlug' => 'required|string|max:50|unique:project_version_tag_group,slug,' . $this->versionTagGroupId,
            'versionTagGroupProjectTypes' => 'required|array',
        ];
    }

    public function updatedVersionTagGroupName(){
        $this->versionTagGroupSlug = ProjectVersionTagGroup::createSlug($this->versionTagGroupName);

        $this->resetValidation('versionTagGroupSlug');
        $this->validateOnly('versionTagGroupSlug');
    }

    public function updatedVersionTagGroupSlug(){
        $this->resetValidation('versionTagGroupSlug');
        $this->validateOnly('versionTagGroupSlug');
    }

    public function createVersionTagGroup()
    {
        $this->resetVersionTagGroupForm();
        $this->isEditingVersionTagGroup = false;
        $this->showModal = true;
    }

    public function editVersionTagGroup($id)
    {
        $versionTagGroup = ProjectVersionTagGroup::find($id);
        if (! $versionTagGroup) {
            $this->error('Version tag group not found.');

            return;
        }

        $this->versionTagGroupId = $versionTagGroup->id;
        $this->versionTagGroupName = $versionTagGroup->name;
        $this->versionTagGroupSlug = $versionTagGroup->slug;
        $this->versionTagGroupProjectTypes = $versionTagGroup->projectTypes->pluck('id')->toArray();
        $this->isEditingVersionTagGroup = true;
        $this->showModal = true;
    }

    public function saveVersionTagGroup()
    {
        $this->validate();

        if ($this->isEditingVersionTagGroup) {
            $versionTagGroup = ProjectVersionTagGroup::find($this->versionTagGroupId);
            $versionTagGroup->name = $this->versionTagGroupName;
            $versionTagGroup->slug = $this->versionTagGroupSlug;
            $versionTagGroup->save();

            // Sync project types
            $versionTagGroup->projectTypes()->sync($this->versionTagGroupProjectTypes);

            $this->success('Version tag group updated successfully.');
        } else {
            $versionTagGroup = ProjectVersionTagGroup::create([
                'name' => $this->versionTagGroupName,
                'slug' => $this->versionTagGroupSlug,
            ]);

            // Sync project types
            $versionTagGroup->projectTypes()->sync($this->versionTagGroupProjectTypes);

            $this->success('Version tag group created successfully.');
        }

        $this->resetVersionTagGroupForm();
        $this->showModal = false;
    }

    public function resetVersionTagGroupForm()
    {
        $this->versionTagGroupId = null;
        $this->versionTagGroupName = '';
        $this->versionTagGroupSlug = '';
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
            $this->success('Version tag group deleted successfully.');
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
