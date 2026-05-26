<?php

namespace App\Livewire;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectType;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class ProjectManager extends Component
{
    use WithFileUploads;
    use Toast;

    #[Locked]
    public ProjectType $projectType;
    #[Locked]
    public Project $project;

    public string $currentSection = 'general';

    // General section
    public string $name = '';
    public string $slug = '';
    public string $summary = '';
    public string $status = 'active';
    public mixed $logo = null;
    public bool $shouldRemoveLogo = false;

    // Description section
    public ?string $description = '';

    // Tags section
    public array $selectedTags = [];

    // Links section
    public ?string $website = '';
    public ?string $issues = '';
    public ?string $source = '';
    public array $externalCredits = [];

    // Members section
    public string $newMemberName = '';
    public string $newMemberRole = 'contributor';

    // Danger section
    public string $deleteConfirmation = '';

    private ProjectService $projectService;

    private bool $strictValidation = false;

    public array $sections = ['general', 'description', 'tags', 'links', 'members', 'analytics', 'danger'];

    protected function rules(): array
    {
        $strictValidation = $this->strictValidation || $this->project->isApproved();

        $rules = [
            'name' => 'required|string|max:255',
            'summary' => 'required|string|max:125',
            'description' => $strictValidation ? 'required|string' : 'nullable|string',
            'logo' => 'nullable|image|max:1024',
            'website' => 'nullable|url|max:255',
            'issues' => 'nullable|url|max:255',
            'source' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
            'selectedTags' => $strictValidation
                ? ['required', 'array', 'min:1', function ($attribute, $value, $fail) {
                    $this->validateTagsForProjectType($value, $fail);
                }]
                : ['array', function ($attribute, $value, $fail) {
                    $this->validateTagsForProjectType($value, $fail);
                }],
            'externalCredits' => 'nullable|array',
            'externalCredits.*.name' => 'required|string|max:255',
            'externalCredits.*.role' => 'required|string|max:255',
            'externalCredits.*.url' => 'nullable|url|max:255',
        ];

        $rules['slug'] = 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:project,slug,' . $this->project->id;

        return $rules;
    }

    private function validateTagsForProjectType(array $selectedTagIds, callable $fail): void
    {
        if (empty($selectedTagIds)) {
            return;
        }

        $selectedTags = \App\Models\ProjectTag::with('parent')->whereIn('id', $selectedTagIds)->get();

        $invalidMainTags = [];
        $invalidSubTags = [];

        foreach ($selectedTags as $tag) {
            if ($tag->isSubTag()) {
                $parentValid = $tag->parent->projectTypes()
                    ->where('project_type_id', $this->projectType->id)
                    ->exists();

                if (!$parentValid) {
                    $invalidSubTags[] = $tag->name;
                }
            } else {
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

    public function mount($projectType, $project, $section = 'general')
    {
        $this->projectType = $projectType;
        $this->project = $project;
        $this->strictValidation = $this->project->isApproved();

        if (!Auth::check()) {
            session()->flash('error', 'Please log in to manage project.');
            return redirect()->route('login', ['projectType' => $projectType]);
        }

        if ($project->isDeactivated()) {
            session()->flash('error', 'This project has been deactivated and cannot be edited.');
            return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project]);
        }

        if (!Gate::allows('update', $project)) {
            session()->flash('error', 'You do not have permission to edit this project.');
            return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project]);
        }

        $this->project->load(['owner', 'tags.tagGroup', 'memberships.user', 'externalCredits']);
        $this->loadProjectData();

        $this->currentSection = $section;

        if (!in_array($this->currentSection, $this->sections)) {
            $this->currentSection = 'general';
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

        $memberships = $this->project->memberships()->with('user')->get();

        $roles = ['owner', 'member', 'maintainer', 'contributor', 'tester', 'translator'];

        return view('livewire.project-manager', [
            'tagGroups' => $tagGroups,
            'memberships' => $memberships,
            'roles' => $roles,
            'approvalStatus' => $this->project->approval_status,
            'rejectionReason' => $this->project->rejection_reason,
            'isDraft' => $this->project->isDraft(),
            'isRejected' => $this->project->isRejected(),
        ]);
    }

    public function updatedName(): void
    {
        // No longer auto-generate slug when editing
    }

    public function updatedSlug(): void
    {
        $this->resetValidation('slug');
        $slug_rules = $this->rules()['slug'];
        $this->validate(['slug' => $slug_rules]);
    }

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
        if (!Gate::allows('update', $this->project)) {
            $this->error('You do not have permission to submit this project for review.');
            return;
        }

        if (!$this->project->isDraft() && !$this->project->isRejected()) {
            $this->error('Only draft or rejected projects can be submitted for review.');
            return;
        }

        $this->project->refresh();
        $this->project->load(['tags.tagGroup', 'externalCredits']);
        $this->loadProjectData();

        $this->strictValidation = true;
        $this->validate();

        try {
            $this->projectService->submitProjectForReview($this->project);
            $this->project->refresh();

            Log::info('Project submitted for review by user', [
                'project_id' => $this->project->id,
                'user_id' => Auth::id(),
            ]);

            $message = config('projects.auto_approve', false)?
                "Project published!" :
                "Project submitted for review!"
            ;

            $this->success($message, redirectTo: route('project.show', ['projectType' => $this->project->projectType, 'project' => $this->project]));
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

        try {
            $logoPath = null;
            if ($this->logo) {
                $logoPath = $this->logo->store('project-logos', 'public');
            } elseif ($this->shouldRemoveLogo) {
                $logoPath = '';
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

            $project = $this->projectService->saveProject($this->project, Auth::user(), $data, $logoPath);

            Log::info('Project saved', [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
            ]);

            $this->dispatch('project-manager-save-succeeded');

            $this->success('Project updated successfully!', redirectTo: route('project.manage', ['projectType' => $project->projectType, 'project' => $project, 'section' => $this->currentSection]));
        } catch (\Exception $e) {
            Log::error('Failed to save project', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('project-manager-save-failed');
            $this->error('Failed to save project');
        }
    }

    public function addMember()
    {
        if (!Gate::allows('addMember', $this->project)) {
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
        if (Gate::denies('delete', $membership)) {
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
        if (Gate::denies('setPrimary', $membership)) {
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
        if (!Gate::allows('delete', $this->project)) {
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

            $this->success('Project deleted successfully. Members can still see it for 14 days.', redirectTo: route('platform.projects'));
        } catch (\Exception $e) {
            Log::error('Failed to delete project', [
                'project_id' => $this->project->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to delete project');
        }
    }

    public function setSection(string $section): void
    {
        if (in_array($section, $this->sections)) {
            $this->currentSection = $section;
        }
    }
}
