<?php

namespace App\Livewire;

use App\Models\ProjectType;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectTagGroup;
use App\Models\User;
use App\Notifications\MembershipInvitation;
use App\Notifications\MembershipRemoved;
use App\Notifications\PrimaryStatusChanged;
use App\Notifications\ProjectDeleted;
use App\Notifications\BrokenDependencyNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

// TODO: refactor the logic into a service
class ProjectForm extends Component
{
    use WithFileUploads;

    public ProjectType $projectType;
    public ?Project $project = null;
    public $isEditing = false;

    public $name = '';
    public $slug = '';
    public $summary = '';
    public $description = '';
    public $logo = null;
    public $removeLogo = false;
    public $website = '';
    public $issues = '';
    public $source = '';
    public $status = 'active';
    public $selectedTags = [];

    // Membership management
    public $newMemberName = '';
    public $newMemberRole = 'contributor';

    // Project deletion
    public $deleteConfirmation = '';

    public function mount($projectType, $project = null)
    {
        $this->projectType = $projectType;

        if (!Auth::check()) {
            return redirect()->route('login', ['projectType' => $projectType])
                ->with('error', 'Please log in to create a project.');
        }

        if ($project && $project->exists) {
            $this->project = $project;
            $this->isEditing = true;

            if (!Gate::allows('update', $project)) {
                return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project])
                    ->with('error', 'You do not have permission to edit this project.');
            }

            $this->project->load(['owner', 'tags.tagGroup', 'memberships.user']);

            $this->name = $this->project->name;
            $this->slug = $this->project->slug;
            $this->summary = $this->project->summary;
            $this->description = $this->project->description;
            $this->website = $this->project->website;
            $this->issues = $this->project->issues;
            $this->source = $this->project->source;
            $this->status = $this->project->status;

            $this->selectedTags = $this->project->tags->pluck('id')->toArray();

        } else {

            if (!Gate::allows('create', Project::class)) {
                return redirect()->route('project-search', ['projectType' => $projectType])
                    ->with('error', 'You do not have permission to create a project.');
            }
        }
    }

    public function render()
    {
        // TODO: refactor this
        $tags = ProjectTag::whereHas('projectTypes', function ($query) {
            $query->where('project_type_id', $this->projectType->id);
        })->with('tagGroup')->get();

        // TODO: refactor this
        $tagGroups = ProjectTagGroup::whereHas('projectTypes', function ($query) {
            $query->where('project_type_id', $this->projectType->id);
        })->with(['tags' => function ($query) {
            $query->whereHas('projectTypes', function ($subQuery) {
                $subQuery->where('project_type_id', $this->projectType->id);
            });
        }])->get();

        if ($this->isEditing) {
            $memberships = $this->project->memberships()->with('user')->get();
            $roles = ['owner', 'member', 'maintainer', 'contributor', 'tester', 'translator'];
        } else {
            $memberships = collect();
            $roles = [];
        }

        return view('livewire.project-form', [
            'tags' => $tags,
            'tagGroups' => $tagGroups,
            'memberships' => $memberships,
            'roles' => $roles
        ]);
    }

    /**
     * Generate a slug from the project name
     */
    public function generateSlug()
    {
        if ($this->isEditing) {
            $this->slug = $this->project->generateSlug($this->name);
        } else {
            $project = new Project();
            $this->slug = $project->generateSlug($this->name);
        }

        return true;
    }

    /**
     * Toggle the project status between active and inactive
     */
    public function toggleStatus()
    {
        $this->status = $this->status === 'active' ? 'inactive' : 'active';
    }

    // TODO: refactor this
    public function save()
    {

        $rules = [
            'name' => 'required|string|max:255',
            'summary' => 'required|string|max:500',
            'description' => 'required|string',
            'logo' => 'nullable|image|max:1024',
            'website' => 'nullable|url|max:255',
            'issues' => 'nullable|url|max:255',
            'source' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
            'selectedTags' => 'required|array|min:1',
        ];

        // Add slug validation with unique constraint that excludes the current project when editing
        if ($this->isEditing) {
            $rules['slug'] = 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:project,slug,' . $this->project->id;
        } else {
            $rules['slug'] = 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:project,slug';
        }

        $this->validate($rules);

        if ($this->isEditing) {
            if (!Auth::check() || !Gate::allows('update', $this->project)) {
                session()->flash('error', 'You do not have permission to edit this project.');
                return redirect()->route('project.show', ['projectType' => $this->project->projectType, 'project' => $this->project]);
            }


            $logoPath = $this->project->logo_path;

            if ($this->removeLogo && $logoPath) {

                Storage::disk('public')->delete($logoPath);
                $logoPath = null;
            } elseif ($this->logo) {

                if ($logoPath) {
                    Storage::disk('public')->delete($logoPath);
                }

                $logoPath = $this->logo->store('project-logos', 'public');
            }


            $this->project->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'summary' => $this->summary,
                'description' => $this->description,
                'logo_path' => $logoPath,
                'website' => $this->website,
                'issues' => $this->issues,
                'source' => $this->source,
                'status' => $this->status,
            ]);


            $this->project->tags()->sync($this->selectedTags);

            $message = 'Project updated successfully!';
            $redirectProjectType = $this->project->projectType;
            $redirectProject = $this->project;
        } else {

            $logoPath = null;
            if ($this->logo) {
                $logoPath = $this->logo->store('project-logos', 'public');
            }


            $project = Project::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'summary' => $this->summary,
                'description' => $this->description,
                'logo_path' => $logoPath,
                'website' => $this->website,
                'issues' => $this->issues,
                'source' => $this->source,
                'status' => $this->status,
                'project_type_id' => $this->projectType->id,
            ]);


            $project->tags()->attach($this->selectedTags);

            $membership = new Membership([
                'role' => 'owner',
                'primary' => true,
            ]);
            $membership->user()->associate(Auth::user());
            $membership->project()->associate($project);
            $membership->save();

            $message = 'Project created successfully!';
            $redirectProjectType = $this->projectType;
            $redirectProject = $project;
        }

        return redirect()->route('project.show', ['projectType' => $redirectProjectType, 'project' => $redirectProject])
            ->with('message', $message);
    }

    /**
     * Add a new member to the project
     */
    public function addMember()
    {
        if (!$this->isEditing) {
            return;
        }

        if (!Auth::check() || !Gate::allows('addMember', $this->project)) {
            session()->flash('error', 'You do not have permission to add members to this project.');
            return redirect()->route('project.show', ['projectType' => $this->project->projectType, 'project' => $this->project]);
        }

        $this->validate([
            'newMemberName' => 'required|string|exists:users,name',
            'newMemberRole' => 'required|in:owner,contributor,tester,translator',
        ], [
            'newMemberName.exists' => 'No user found with this name.'
        ]);

        $user = User::where('name', $this->newMemberName)->first();

        if ($this->project->users()->where('user_id', $user->id)->exists()) {
            $this->addError('newMemberName', 'This user is already a member of the project.');
            return;
        }

        $membership = new Membership([
            'role' => $this->newMemberRole,
            'primary' => false,
            'status' => 'pending',
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($this->project);
        $membership->inviter()->associate(Auth::user());
        $membership->save();

        $user->notify(new MembershipInvitation($membership));

        $this->newMemberName = '';
        $this->newMemberRole = 'contributor';

        $this->project->refresh();
        $this->project->load('memberships.user');

        session()->flash('message', 'Invitation sent successfully!');
    }

    /**
     * Remove a member from the project
     *
     * @param int $membershipId
     */
    public function removeMember($membershipId)
    {
        if (!$this->isEditing) {
            return;
        }

        if (!Auth::check() || !Gate::allows('removeMember', $this->project)) {
            session()->flash('error', 'You do not have permission to remove members from this project.');
            return redirect()->route('project.show', ['projectType' => $this->project->projectType, 'project' => $this->project]);
        }
        $membership = Membership::findOrFail($membershipId);

        if ($membership->project_id !== $this->project->id) {
            session()->flash('error', 'Invalid membership.');
            return;
        }

        if ($membership->user_id === Auth::id()) {
            if ($membership->primary) {
                session()->flash('error', 'You cannot remove yourself as the primary owner. Transfer ownership to another member first.');
                return;
            }
        }

        if ($membership->primary && $this->project->memberships()->where('primary', true)->count() <= 1) {
            session()->flash('error', 'You cannot remove the last primary member of the project.');
            return;
        }

        $isSelfRemoval = $membership->user_id === Auth::id();

        $removedUser = User::find($membership->user_id);
        $currentUser = Auth::user();

        DB::beginTransaction();

        try {
            $projectMembers = $this->project->active_users()->get();

            $membership->delete();

            foreach ($projectMembers as $member) {
                $member->notify(new MembershipRemoved($this->project, $removedUser, $currentUser, $isSelfRemoval));
            }

            DB::commit();

            if ($isSelfRemoval) {
                session()->flash('message', 'You have left the project successfully!');
                return redirect()->route('project-search', ['projectType' => $this->projectType->value]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to remove member: ' . $e->getMessage());
            return;
        }

        $this->project->refresh();
        $this->project->load('memberships.user');

        session()->flash('message', 'Member removed successfully!');
    }

    /**
     * Set a member as primary for the project
     *
     * @param int $membershipId
     */
    public function setPrimaryMember($membershipId)
    {
        if (!$this->isEditing) {
            return;
        }

        if (!Auth::check() || !Gate::allows('removeMember', $this->project)) {
            session()->flash('error', 'You do not have permission to manage project ownership.');
            return redirect()->route('project.show', ['projectType' => $this->project->projectType, 'project' => $this->project]);
        }

        $membership = Membership::findOrFail($membershipId);

        if ($membership->project_id !== $this->project->id) {
            session()->flash('error', 'Invalid membership.');
            return;
        }

        if ($membership->status !== 'active') {
            session()->flash('error', 'Only active members can be set as primary.');
            return;
        }

        if ($membership->primary) {
            session()->flash('error', 'This member is already a primary member.');
            return;
        }

        if (!$this->project->memberships()->where('user_id', Auth::id())->where('primary', true)->exists()) {
            session()->flash('error', 'Only primary owners can transfer ownership.');
            return;
        }

        DB::beginTransaction();

        try {
            $this->project->memberships()
                ->where('id', '!=', $membership->id)
                ->where('primary', true)
                ->update(['primary' => false]);

            $membership->update(['primary' => true]);

            $newPrimaryUser = User::find($membership->user_id);
            $currentUser = Auth::user();

            $projectMembers = $this->project->active_users()->get();

            foreach ($projectMembers as $member) {
                $member->notify(new PrimaryStatusChanged($this->project, $newPrimaryUser, $currentUser));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to update primary member: ' . $e->getMessage());
            return;
        }

        $this->project->refresh();
        $this->project->load('memberships.user');

        session()->flash('message', 'Member set as primary successfully! All other members are no longer primary.');
    }

    /**
     * Delete the project (soft delete)
     */
    public function deleteProject()
    {
        if (!$this->isEditing) {
            return;
        }

        if (!Auth::check() || !Gate::allows('delete', $this->project)) {
            session()->flash('error', 'You do not have permission to delete this project.');
            return;
        }

        $this->validate([
            'deleteConfirmation' => 'required|in:' . $this->project->name,
        ], [
            'deleteConfirmation.in' => 'The project name you entered does not match. Please enter the exact project name to confirm deletion.'
        ]);

        $projectType = $this->project->projectType;

        $projectMembers = $this->project->active_users()->get();

        DB::beginTransaction();

        try {
            $dependentProjectVersions = $this->project->dependedOnBy()->with(['projectVersion.project.owner'])->get();

            $projectVersionIds = $this->project->versions()->pluck('id')->toArray();
            $dependentVersions = \App\Models\ProjectVersionDependency::whereIn('dependency_project_version_id', $projectVersionIds)
                ->with(['projectVersion.project.owner'])
                ->get();
            $dependentProjects = [];

            foreach ($dependentProjectVersions as $dependency) {
                if ($dependency->projectVersion && $dependency->projectVersion->project) {
                    $project = $dependency->projectVersion->project;
                    $version = $dependency->projectVersion;

                    if (!isset($dependentProjects[$project->id])) {
                        $dependentProjects[$project->id] = [
                            'project' => $project,
                            'versions' => []
                        ];
                    }

                    $dependentProjects[$project->id]['versions'][] = $version;
                }
            }

            foreach ($dependentVersions as $dependency) {
                if ($dependency->projectVersion && $dependency->projectVersion->project) {
                    $project = $dependency->projectVersion->project;
                    $version = $dependency->projectVersion;

                    if (!isset($dependentProjects[$project->id])) {
                        $dependentProjects[$project->id] = [
                            'project' => $project,
                            'versions' => []
                        ];
                    }

                    $versionIds = array_map(function ($v) {
                        return $v->id;
                    }, $dependentProjects[$project->id]['versions']);

                    if (!in_array($version->id, $versionIds)) {
                        $dependentProjects[$project->id]['versions'][] = $version;
                    }
                }
            }

            $this->project->delete();

            foreach ($projectMembers as $member) {
                $member->notify(new ProjectDeleted($this->project, Auth::user()));
            }

            foreach ($dependentProjects as $projectData) {
                $project = $projectData['project'];
                $owners = $project->owner;

                foreach ($owners as $owner) {
                    foreach ($projectData['versions'] as $version) {
                        $owner->notify(new BrokenDependencyNotification(
                            $project,
                            $version,
                            $this->project->id,
                            $this->project->name,
                            null,
                            Auth::user(),
                            true
                        ));
                    }
                }
            }

            DB::commit();

            return redirect()->route('project-search', ['projectType' => $projectType])
                ->with('message', 'Project deleted successfully. Members can still see it in their profile for the next 14 days.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete project: ' . $e->getMessage(), [
                'project_id' => $this->project->id,
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'Failed to delete project: ' . $e->getMessage());
            return;
        }
    }
}
