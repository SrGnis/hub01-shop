<?php

namespace App\Livewire;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectType;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class ProjectForm extends Component
{
    use WithFileUploads;
    use Toast;

    #[Locked]
    public ProjectType $projectType;
    #[Locked]
    public ?Project $project = null;
    #[Locked]
    public bool $isEditing = false;

    public string $name = '';
    public string $slug = '';
    public string $summary = '';
    public string $description = '';
    public mixed $logo = null;
    public bool $shouldRemoveLogo = false;
    public string $website = '';
    public string $issues = '';
    public string $source = '';
    public string $status = 'active';
    public array $selectedTags = [];

    // Membership management
    public string $newMemberName = '';
    public string $newMemberRole = 'contributor';

    // Project deletion
    public string $deleteConfirmation = '';

    private ProjectService $projectService;

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'summary' => 'required|string|max:125',
            'description' => 'required|string',
            'logo' => 'nullable|image|max:1024',
            'website' => 'nullable|url|max:255',
            'issues' => 'nullable|url|max:255',
            'source' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
            'selectedTags' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $this->validateTagsForProjectType($value, $fail);
                },
            ],
        ];

        if ($this->isEditing) {
            $rules['slug'] = 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:project,slug,' . $this->project->id;
        } else {
            $rules['slug'] = 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:project,slug';
        }

        return $rules;
    }

    /**
     * Validate that all selected tags belong to tag groups valid for the current project type.
     */
    private function validateTagsForProjectType(array $selectedTagIds, callable $fail): void
    {
        if (empty($selectedTagIds)) {
            return;
        }

        $invalidTags = \App\Models\ProjectTag::whereIn('id', $selectedTagIds)
            ->whereDoesntHave('projectTypes', fn ($query) => $query->where('project_type_id', $this->projectType->id))
            ->pluck('name')
            ->toArray();

        if (!empty($invalidTags)) {
            $tagNames = implode(', ', $invalidTags);
            $fail("The following tags are not allowed for this project type: {$tagNames}.");
        }
    }

    public function boot(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function mount($projectType, $project = null)
    {
        $this->projectType = $projectType;

        if (!Auth::check()) {
            $this->error('Please log in to create a project.', redirectTo: route('login'));
        }

        if ($project && $project->exists) {
            $this->project = $project;
            $this->isEditing = true;

            if (!Gate::allows('update', $project)) {
                $this->error('You do not have permission to edit this project.', redirectTo: route('project.show', ['projectType' => $projectType, 'project' => $project]));
            }

            $this->project->load(['owner', 'tags.tagGroup', 'memberships.user']);
            $this->loadProjectData();
        } else {
            if (!Gate::allows('create', Project::class)) {
                $this->error('You do not have permission to create a project.', redirectTo: route('project-search', ['projectType' => $projectType]));
            }
        }
    }

    private function loadProjectData(): void
    {
        $this->name = $this->project->name;
        $this->slug = $this->project->slug;
        $this->summary = $this->project->summary;
        $this->description = $this->project->description;
        $this->website = $this->project->website;
        $this->issues = $this->project->issues;
        $this->source = $this->project->source;
        $this->status = $this->project->status;
        $this->selectedTags = $this->project->tags->pluck('id')->toArray();
    }

    public function render()
    {
        $tagGroups = $this->projectService->getTagGroupsForProjectType($this->projectType);

        $memberships = $this->isEditing
            ? $this->project->memberships()->with('user')->get()
            : collect();

        $roles = $this->isEditing ? ['owner', 'member', 'maintainer', 'contributor', 'tester', 'translator'] : [];

        return view('livewire.project-form', [
            'tagGroups' => $tagGroups,
            'memberships' => $memberships,
            'roles' => $roles,
        ]);
    }

    public function updatedName(): void
    {
        if ($this->isEditing) {
            return;
        }
        $this->generateSlug();
    }

    public function updatedslug(): void
    {
        $this->resetValidation('slug');
        $slug_rules = $this->rules()['slug'];
        $this->validate(['slug' => $slug_rules]);
    }

    // dummy method for attaching the loading state
    public function refreshMarkdown(): void {}

    public function generateSlug(): void
    {
        $this->slug = $this->projectService->generateSlug($this->name, $this->project);
        $this->resetValidation('slug');
    }

    public function removeLogo(): void
    {
        $this->logo = null;
        $this->shouldRemoveLogo = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->isEditing && !Gate::allows('update', $this->project)) {
            $this->error('You do not have permission to edit this project.', redirectTo: route('project.show', ['projectType' => $this->project->projectType, 'project' => $this->project]));
        }

        $logoPath = null;
        if ($this->logo) {
            $logoPath = $this->logo->store('project-logos', 'public');
        } elseif ($this->shouldRemoveLogo && $this->isEditing) {
            $logoPath = '';  // Empty string signals removal
        }

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'description' => $this->description,
            'website' => $this->website,
            'issues' => $this->issues,
            'source' => $this->source,
            'status' => $this->status,
            'selectedTags' => $this->selectedTags,
        ];

        if (!$this->isEditing) {
            $data['project_type_id'] = $this->projectType->id;
        }

        $project = $this->projectService->saveProject($this->project, Auth::user(), $data, $logoPath);

        $message = $this->isEditing ? 'Project updated successfully!' : 'Project created successfully!';

        $this->success($message, redirectTo: route('project.show', ['projectType' => $project->projectType, 'project' => $project]));
    }

    public function addMember()
    {
        if (!$this->isEditing || Gate::denies('addMember', $this->project)) {
            $this->error('You do not have permission to add members.');
            return;
        }

        $this->validate([
            'newMemberName' => 'required|string|exists:users,name',
            'newMemberRole' => 'required|in:owner,member,contributor,tester,translator',
        ], ['newMemberName.exists' => 'No user found with this name.']);

        try {
            $this->projectService->addMember($this->project, $this->newMemberName, $this->newMemberRole);
            $this->newMemberName = '';
            $this->newMemberRole = 'contributor';
            $this->project->refresh();
            $this->project->load('memberships.user');
            $this->success('Invitation sent successfully!');
        } catch (\Exception $e) {
            $this->addError('newMemberName', $e->getMessage());
        }
    }

    public function removeMember($membershipId)
    {
        $membership = Membership::findOrFail($membershipId);
        if (!$this->isEditing || Gate::denies('delete', $membership)) {
            $this->error('You do not have permission to remove members.');
            return;
        }

        try {
            $isSelfRemoval = $this->projectService->removeMember($this->project, $membershipId);

            if ($isSelfRemoval) {
                $this->success('You have left the project successfully!', redirectTo: route('project-search', ['projectType' => $this->projectType->value]));
            }

            $this->project->refresh();
            $this->project->load('memberships.user');
            $this->success('Member removed successfully!');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function setPrimaryMember($membershipId)
    {
        $membership = Membership::findOrFail($membershipId);
        if (!$this->isEditing || Gate::denies('setPrimary', $membership)) {
            $this->error('You do not have permission to manage ownership.');
            return;
        }

        try {
            $this->projectService->setPrimaryMember($this->project, $membershipId);
            $this->project->refresh();
            $this->project->load('memberships.user');
            $this->success('Member set as primary successfully!');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function deleteProject()
    {
        if (!$this->isEditing || !Gate::allows('delete', $this->project)) {
            $this->error('You do not have permission to delete this project.');
            return;
        }

        $this->validate([
            'deleteConfirmation' => 'required|in:' . $this->project->name,
        ], ['deleteConfirmation.in' => 'The project name you entered does not match.']);

        try {
            $projectType = $this->project->projectType;
            $this->projectService->deleteProject($this->project);

            $this->success('Project deleted successfully. Members can still see it for 14 days.', redirectTo: route('project-search', ['projectType' => $projectType]));
        } catch (\Exception $e) {
            $this->error('Failed to delete project: ' . $e->getMessage());
        }
    }
}

