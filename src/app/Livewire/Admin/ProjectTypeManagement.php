<?php

namespace App\Livewire\Admin;

use App\Models\ProjectType;
use Livewire\Component;

class ProjectTypeManagement extends Component
{
    public $projectTypeId = null;

    public $projectTypeValue = '';

    public $projectTypeDisplayName = '';

    public $projectTypeIcon = 'lucide-package';

    public $isEditingProjectType = false;

    // Confirmation
    public $confirmingDeletion = false;

    public $itemToDelete = null;

    protected function rules()
    {
        return [
            'projectTypeValue' => 'required|string|max:50',
            'projectTypeDisplayName' => 'required|string|max:50',
            'projectTypeIcon' => 'required|string|max:50|starts_with:lucide-',
        ];
    }

    protected function messages()
    {
        return [
            'projectTypeIcon.starts_with' => 'The icon must be a valid Lucide icon (starts with "lucide-").',
        ];
    }

    public function createProjectType()
    {
        $this->resetProjectTypeForm();
        $this->isEditingProjectType = false;
    }

    public function editProjectType($id)
    {
        $projectType = ProjectType::find($id);
        if (! $projectType) {
            session()->flash('error', 'Project type not found.');

            return;
        }

        $this->projectTypeId = $projectType->id;
        $this->projectTypeValue = $projectType->value;
        $this->projectTypeDisplayName = $projectType->display_name;
        $this->projectTypeIcon = $projectType->icon;
        $this->isEditingProjectType = true;
    }

    public function saveProjectType()
    {
        $this->validate();

        if ($this->isEditingProjectType) {
            $projectType = ProjectType::find($this->projectTypeId);
            $projectType->value = $this->projectTypeValue;
            $projectType->display_name = $this->projectTypeDisplayName;
            $projectType->icon = $this->projectTypeIcon;
            $projectType->save();

            session()->flash('message', 'Project type updated successfully.');
        } else {
            ProjectType::create([
                'value' => $this->projectTypeValue,
                'display_name' => $this->projectTypeDisplayName,
                'icon' => $this->projectTypeIcon,
            ]);

            session()->flash('message', 'Project type created successfully.');
        }

        $this->resetProjectTypeForm();
    }

    public function resetProjectTypeForm()
    {
        $this->projectTypeId = null;
        $this->projectTypeValue = '';
        $this->projectTypeDisplayName = '';
        $this->projectTypeIcon = 'lucide-package';
        $this->isEditingProjectType = false;
    }

    public function confirmDeletion($id)
    {
        $this->confirmingDeletion = true;
        $this->itemToDelete = $id;
    }

    public function deleteItem()
    {
        $item = ProjectType::find($this->itemToDelete);

        if ($item) {
            $item->delete();
            session()->flash('message', 'Project type deleted successfully.');
        }

        $this->confirmingDeletion = false;
        $this->itemToDelete = null;
    }

    public function render()
    {
        return view('livewire.admin.project-type-management', [
            'projectTypes' => ProjectType::all(),
        ]);
    }
}
