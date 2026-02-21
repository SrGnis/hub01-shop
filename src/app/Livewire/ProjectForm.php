<?php

namespace App\Livewire;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
    public array $externalCredits = [];

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
            'externalCredits' => 'nullable|array',
            'externalCredits.*.name' => 'required|string|max:255',
            'externalCredits.*.role' => 'required|string|max:255',
            'externalCredits.*.url' => 'nullable|url|max:255',
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
     *
     * Main Tags need to be valid for the project type.
     * Sub Tags only need that their parent tag is valid for the project type.
     *
     * @param array $selectedTagIds The selected tag IDs
     * @param callable $fail The validation failure callback
     */
    private function validateTagsForProjectType(array $selectedTagIds, callable $fail): void
    {
        if (empty($selectedTagIds)) {
            return;
        }

        // Get all selected tags with their parent relationships
        $selectedTags = ProjectTag::with('parent')->whereIn('id', $selectedTagIds)->get();

        $invalidMainTags = [];
        $invalidSubTags = [];

        foreach ($selectedTags as $tag) {
            if ($tag->isSubTag()) {
                // Sub tags: check if parent is valid for project type
                $parentValid = $tag->parent->projectTypes()
                    ->where('project_type_id', $this->projectType->id)
                    ->exists();

                if (!$parentValid) {
                    $invalidSubTags[] = $tag->name;
                }
            } else {
                // Main tags: check if they are valid for project type
                $tagValid = $tag->projectTypes()
                    ->where('project_type_id', $this->projectType->id)
                    ->exists();

                if (!$tagValid) {
                    $invalidMainTags[] = $tag->name;
                }
            }
        }

        $allInvalidTags = array_merge($invalidMainTags, $invalidSubTags);

        if (!empty($allInvalidTags)) {
            $tagNames = implode(', ', $allInvalidTags);
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
            // use normal laravel flash message toast is not working here
            session()->flash('error', 'Please log in to create a project.');
            return redirect()->route('login', ['projectType' => $projectType]);
        }

        if ($project && $project->exists) {
            $this->project = $project;
            $this->isEditing = true;

            // Check if the project is deactivated
            if ($project->isDeactivated()) {

                session()->flash('error', 'This project has been deactivated and cannot be edited.');
                return redirect()->route('project-search', ['projectType' => $projectType]);

                return;
            }

            if (! Gate::allows('update', $project)) {

                session()->flash('error', 'You do not have permission to edit this project.');
                return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project]);

                return;
            }

            $this->project->load(['owner', 'tags.tagGroup', 'memberships.user', 'externalCredits']);
            $this->loadProjectData();
        } else {
            if (!Gate::allows('create', Project::class)) {

                session()->flash('error', 'You do not have permission to create a project.');
                return redirect()->route('project-search', ['projectType' => $projectType]);
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
        $this->externalCredits = $this->project->externalCredits
            ->map(fn ($credit) => [
                'name' => $credit->name,
                'role' => $credit->role,
                'url' => $credit->url,
            ])
            ->toArray();
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
            'approvalStatus' => $this->isEditing ? $this->project->approval_status : null,
            'rejectionReason' => $this->isEditing ? $this->project->rejection_reason : null,
            'isDraft' => $this->isEditing && $this->project->isDraft(),
            'isRejected' => $this->isEditing && $this->project->isRejected(),
        ]);
    }

    public function updatedName(): void
    {
        if ($this->isEditing) {
            return;
        }
        $this->generateSlug();
    }

    public function updatedSlug(): void
    {
        $this->resetValidation('slug');
        $slug_rules = $this->rules()['slug'];
        $this->validate(['slug' => $slug_rules]);
    }

    // dummy method for attaching the loading state
    public function refreshMarkdown(): void {}

    public function addExternalCredit(): void
    {
        $this->externalCredits[] = [
            'name' => '',
            'role' => '',
            'url' => '',
        ];
    }

    public function removeExternalCredit(int $index): void
    {
        unset($this->externalCredits[$index]);
        $this->externalCredits = array_values($this->externalCredits);
    }

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

    public function sendToReview()
    {
        if (!$this->isEditing) {
            $this->error('Only existing projects can be submitted for review.');
            return;
        }

        if (!Gate::allows('update', $this->project)) {
            $this->error('You do not have permission to submit this project for review.');
            return;
        }

        if (!$this->project->isDraft() && !$this->project->isRejected()) {
            $this->error('Only draft or rejected projects can be submitted for review.');
            return;
        }

        try {
            $this->projectService->submitProjectForReview($this->project);
            $this->project->refresh();

            Log::info('Project submitted for review by user', [
                'project_id' => $this->project->id,
                'user_id' => Auth::id(),
            ]);

            $this->success('Project submitted for review!', redirectTo: route('project.show', ['projectType' => $this->project->projectType, 'project' => $this->project]));
        } catch (\Exception $e) {
            Log::error('Failed to submit project for review', [
                'project_id' => $this->project->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to submit project for review');
        }
    }

    public function save()
    {
        $this->validate();

        if ($this->isEditing && !Gate::allows('update', $this->project)) {
            $this->error('You do not have permission to edit this project.', redirectTo: route('project.show', ['projectType' => $this->project->projectType, 'project' => $this->project]));
        }

        try {
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
                'externalCredits' => $this->externalCredits,
            ];

            if (!$this->isEditing) {
                $data['project_type_id'] = $this->projectType->id;
            }

            $project = $this->projectService->saveProject($this->project, Auth::user(), $data, $logoPath);

            Log::info('Project saved', [
                'project_id' => $project->id,
                'is_new' => !$this->isEditing,
                'user_id' => Auth::id(),
            ]);

            // Determine success message based on auto-approve setting
            if ($this->isEditing) {
                $message = 'Project updated successfully!';
            } else {
                $autoApprove = config('projects.auto_approve', false);
                $message = $autoApprove
                    ? 'Project created and approved!'
                    : 'Project created as draft!';
            }

            $this->success($message, redirectTo: route('project.show', ['projectType' => $project->projectType, 'project' => $project]));
        } catch (\Exception $e) {
            Log::error('Failed to save project', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to save project');
        }
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
            Log::error('Failed to add member to project', [
                'project_id' => $this->project->id,
                'user_id' => Auth::id(),
                'new_member_name' => $this->newMemberName,
                'error' => $e->getMessage(),
            ]);
            $this->addError('newMemberName', 'Failed to add member');
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
            Log::error('Failed to remove member from project', [
                'project_id' => $this->project->id,
                'user_id' => Auth::id(),
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to remove member');
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
            Log::error('Failed to set member as primary', [
                'project_id' => $this->project->id,
                'user_id' => Auth::id(),
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to set member as primary');
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
            $projectId = $this->project->id;
            $this->projectService->deleteProject($this->project);

            Log::info('Project deleted by user', [
                'project_id' => $projectId,
                'user_id' => Auth::id(),
            ]);

            $this->success('Project deleted successfully. Members can still see it for 14 days.', redirectTo: route('project-search', ['projectType' => $projectType]));
        } catch (\Exception $e) {
            Log::error('Failed to delete project', [
                'project_id' => $this->project->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to delete project');
        }
    }
}
