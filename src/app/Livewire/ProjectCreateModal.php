<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectType;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class ProjectCreateModal extends Component
{
    use Toast;

    public bool $isOpen = false;

    public ?string $selectedType = null;

    public string $name = '';

    public string $slug = '';

    public string $summary = '';

    #[Locked]
    public array $projectTypes = [];

    private ProjectService $projectService;

    protected function rules(): array
    {
        return [
            'selectedType' => 'required|exists:project_type,value',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:project,slug',
            'summary' => 'required|string|max:125',
        ];
    }

    public function boot(ProjectService $projectService): void
    {
        $this->projectService = $projectService;
    }

    public function mount(): void
    {
        $this->projectTypes = ProjectType::orderBy('display_name')->get(['value', 'display_name', 'icon'])->toArray();
    }

    #[On('open-project-create-modal')]
    public function open(?string $projectType = null): void
    {
        if (!Auth::check()) {
            session()->flash('error', 'Please log in to create a project.');
            $this->redirectRoute('login');
            return;
        }

        if (!Gate::allows('create', Project::class)) {
            $this->error('You do not have permission to create a project.');
            return;
        }

        $this->selectedType = $projectType;
        $this->name = '';
        $this->slug = '';
        $this->summary = '';
        $this->resetValidation();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function updatedName(): void
    {
        if ($this->name) {
            $this->generateSlug();
        }
    }

    public function updatedSlug(): void
    {
        $this->resetValidation('slug');
        $slugRules = $this->rules()['slug'];
        $this->validate(['slug' => $slugRules]);
    }

    public function generateSlug(): void
    {
        $this->slug = $this->projectService->generateSlug($this->name);
        $this->resetValidation('slug');
    }

    public function create(): void
    {
        $this->validate();

        if (!Auth::check() || !Gate::allows('create', Project::class)) {
            $this->error('You do not have permission to create a project.');
            return;
        }

        try {
            $projectType = ProjectType::where('value', $this->selectedType)->firstOrFail();

            $project = $this->projectService->saveProject(
                null,
                Auth::user(),
                [
                    'name' => $this->name,
                    'slug' => $this->slug,
                    'summary' => $this->summary,
                    'description' => '',
                    'status' => 'active',
                    'selectedTags' => [],
                    'externalCredits' => [],
                    'project_type_id' => $projectType->id,
                ]
            );

            $this->close();
            $this->success('Project created as draft!', redirectTo: route('project.manage', ['projectType' => $projectType->value, 'project' => $project]));
        } catch (\Exception $e) {
            $this->error('Failed to create project: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.project-create-modal', [
            'projectTypes' => $this->projectTypes,
        ]);
    }
}
