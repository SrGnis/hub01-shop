<?php

namespace App\Livewire\Admin;

use App\Models\ProjectType;
use App\Models\ProjectVersionTag;
use App\Models\ProjectVersionTagGroup;
use Livewire\Component;
use Mary\Traits\Toast;

class VersionTagManagement extends Component
{
    use Toast;

    public $versionTagId = null;

    public $versionTagName = '';

    public $versionTagIcon = 'lucide-tag';

    public $versionTagGroupId = null;

    public $versionTagProjectTypes = [];

    public $isEditingVersionTag = false;

    // Modal state
    public $showModal = false;

    // Confirmation
    public $confirmingDeletion = false;

    public $itemToDelete = null;

    protected function rules()
    {
        return [
            'versionTagName' => 'required|string|max:50',
            'versionTagIcon' => 'required|string|max:50|starts_with:lucide-',
        ];
    }

    protected function messages()
    {
        return [
            'versionTagIcon.starts_with' => 'The icon must be a valid Lucide icon (starts with "lucide-").',
        ];
    }

    public function createVersionTag()
    {
        $this->resetVersionTagForm();
        $this->isEditingVersionTag = false;
        $this->showModal = true;
    }

    public function editVersionTag($id)
    {
        $versionTag = ProjectVersionTag::find($id);
        if (! $versionTag) {
            $this->error('Version tag not found.');

            return;
        }

        $this->versionTagId = $versionTag->id;
        $this->versionTagName = $versionTag->name;
        $this->versionTagIcon = $versionTag->icon;
        $this->versionTagGroupId = $versionTag->project_version_tag_group_id;
        $this->versionTagProjectTypes = $versionTag->projectTypes->pluck('id')->toArray();
        $this->isEditingVersionTag = true;
        $this->showModal = true;
    }

    public function saveVersionTag()
    {
        $this->validate();

        if ($this->isEditingVersionTag) {
            $versionTag = ProjectVersionTag::find($this->versionTagId);
            $versionTag->name = $this->versionTagName;
            $versionTag->icon = $this->versionTagIcon;
            $versionTag->project_version_tag_group_id = $this->versionTagGroupId;
            $versionTag->save();

            // Sync project types
            $versionTag->projectTypes()->sync($this->versionTagProjectTypes);

            $this->success('Version tag updated successfully.');
        } else {
            $versionTag = ProjectVersionTag::create([
                'name' => $this->versionTagName,
                'icon' => $this->versionTagIcon,
                'project_version_tag_group_id' => $this->versionTagGroupId,
            ]);

            // Sync project types
            $versionTag->projectTypes()->sync($this->versionTagProjectTypes);

            $this->success('Version tag created successfully.');
        }

        $this->resetVersionTagForm();
        $this->showModal = false;
    }

    public function resetVersionTagForm()
    {
        $this->versionTagId = null;
        $this->versionTagName = '';
        $this->versionTagIcon = 'lucide-tag';
        $this->versionTagGroupId = null;
        $this->versionTagProjectTypes = [];
        $this->isEditingVersionTag = false;
    }

    public function confirmDeletion($id)
    {
        $this->confirmingDeletion = true;
        $this->itemToDelete = $id;
    }

    public function deleteItem()
    {
        $item = ProjectVersionTag::find($this->itemToDelete);

        if ($item) {
            $item->delete();
            $this->success('Version tag deleted successfully.');
        }

        $this->confirmingDeletion = false;
        $this->itemToDelete = null;
    }

    public function render()
    {
        return view('livewire.admin.version-tag-management', [
            'versionTags' => ProjectVersionTag::with('tagGroup')->get(),
            'versionTagGroups' => ProjectVersionTagGroup::all(),
            'projectTypes' => ProjectType::all(),
        ]);
    }
}
