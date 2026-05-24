<x-platform.shell :title="'Manage Project: ' . $project->pretty_name" x-data="unsavedChanges()" x-on:project-manager:dirty="markDirty()" x-on:mary-file-changed="markDirty()" @destroy="cleanup()">
    <x-slot:left>
        <x-project.manage-nav :projectType="$projectType" :project="$project" :current="$currentSection" />
    </x-slot:left>

    <x-slot:header>

        {{-- Todo Items --}}
        @if(
            $approvalStatus === \App\Enums\ApprovalStatus::DRAFT &&
            (
                empty($project->description) ||
                $project->tags->isEmpty() ||
                empty($project->logo_path) ||
                (
                    empty($project->website) &&
                    empty($project->issues) &&
                    empty($project->source)
                )
            )
        )
            <p class="text-xs font-semibold uppercase tracking-widest text-base-content/40 mb-3">Before you go live</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">

                @if (empty($project->description))
                    <div class="rounded-xl border border-warning/30 bg-warning/20 p-4 flex flex-col gap-3">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-warning/20 border border-warning/30 p-2 shrink-0">
                                <x-icon name="lucide-file-text" class="w-4 h-4 text-warning" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="inline-block text-xs font-semibold bg-warning/20 text-warning border border-warning/30 rounded-full px-2 py-0.5 mb-1">Required</span>
                                <div class="font-semibold text-sm text-base-content">Add a description</div>
                                <p class="text-xs text-base-content/60 mt-0.5 leading-relaxed">Help users understand what your project does.</p>
                            </div>
                        </div>
                        <a href="{{ route('project.manage', ['projectType' => $project->projectType, 'project' => $project, 'section' => 'description']) }}"
                        class="btn btn-xs bg-warning/20 hover:bg-warning/30 text-warning border border-warning/30 w-full">
                            Add now <x-icon name="lucide-arrow-right" class="w-3 h-3" />
                        </a>
                    </div>
                @endif

                @if ($project->tags->isEmpty())
                    <div class="rounded-xl border border-warning/30 bg-warning/20 p-4 flex flex-col gap-3">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-warning/20 border border-warning/30 p-2 shrink-0">
                                <x-icon name="lucide-tag" class="w-4 h-4 text-warning" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="inline-block text-xs font-semibold bg-warning/20 text-warning border border-warning/30 rounded-full px-2 py-0.5 mb-1">Required</span>
                                <div class="font-semibold text-sm text-base-content">Add a tag</div>
                                <p class="text-xs text-base-content/60 mt-0.5 leading-relaxed">Add at least one tag to categorize your project.</p>
                            </div>
                        </div>
                        <a href="{{ route('project.manage', ['projectType' => $project->projectType, 'project' => $project, 'section' => 'tags']) }}"
                        class="btn btn-xs bg-warning/20 hover:bg-warning/30 text-warning border border-warning/30 w-full">
                            Add now <x-icon name="lucide-arrow-right" class="w-3 h-3" />
                        </a>
                    </div>
                @endif

                @if (empty($project->logo_path))
                    <div class="rounded-xl border border-info/30 bg-info/20 p-4 flex flex-col gap-3">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-info/20 border border-info/30 p-2 shrink-0">
                                <x-icon name="lucide-image" class="w-4 h-4 text-info" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="inline-block text-xs font-semibold bg-info/20 text-info border border-info/30 rounded-full px-2 py-0.5 mb-1">Optional</span>
                                <div class="font-semibold text-sm text-base-content">Add a logo</div>
                                <p class="text-xs text-base-content/60 mt-0.5 leading-relaxed">Upload a logo to make your project stand out.</p>
                            </div>
                        </div>
                        <a href="{{ route('project.manage', ['projectType' => $project->projectType, 'project' => $project, 'section' => 'general']) }}"
                        class="btn btn-xs bg-info/20 hover:bg-info/30 text-info border border-info/30 w-full">
                            Add now <x-icon name="lucide-arrow-right" class="w-3 h-3" />
                        </a>
                    </div>
                @endif

                @if (empty($project->website) && empty($project->issues) && empty($project->source))
                    <div class="rounded-xl border border-info/30 bg-info/20 p-4 flex flex-col gap-3">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-info/20 border border-info/30 p-2 shrink-0">
                                <x-icon name="lucide-link" class="w-4 h-4 text-info" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="inline-block text-xs font-semibold bg-info/20 text-info border border-info/30 rounded-full px-2 py-0.5 mb-1">Optional</span>
                                <div class="font-semibold text-sm text-base-content">Fill some links</div>
                                <p class="text-xs text-base-content/60 mt-0.5 leading-relaxed">Add website, issues tracker, or source repo links.</p>
                            </div>
                        </div>
                        <a href="{{ route('project.manage', ['projectType' => $project->projectType, 'project' => $project, 'section' => 'links']) }}"
                        class="btn btn-xs bg-info/20 hover:bg-info/30 text-info border border-info/30 w-full">
                            Add now <x-icon name="lucide-arrow-right" class="w-3 h-3" />
                        </a>
                    </div>
                @endif

            </div>
        @endif

        {{-- Header Row --}}
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4 bg-base-100 border border-base-300 rounded-xl px-5 py-4">

            {{-- Title --}}
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-base-content/40 mb-0.5">Manage Project</p>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h1 class="text-xl font-bold text-base-content leading-tight">{{ $project->pretty_name }}</h1>
                    <a href="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}"
                       class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:text-primary/80">
                        View project
                        <x-icon name="lucide-external-link" class="w-3.5 h-3.5" />
                    </a>
                </div>
                <p class="text-xs text-base-content/50 mt-0.5">Manage your project settings and content</p>
            </div>

            {{-- Approval Status --}}
            @if ($approvalStatus === \App\Enums\ApprovalStatus::DRAFT)
                <div class="badge badge-info gap-1.5 py-3 px-3 rounded-full text-xs font-medium">
                    <x-icon name="lucide-file-pen-line" class="w-3.5 h-3.5" />
                    Draft {{config('projects.auto_approve', false)? "" : "— submit for review when ready"}}
                </div>
            @elseif ($approvalStatus === \App\Enums\ApprovalStatus::PENDING)
                <div class="badge badge-warning gap-1.5 py-3 px-3 rounded-full text-xs font-medium">
                    <x-icon name="lucide-clock" class="w-3.5 h-3.5" />
                    Pending admin approval
                </div>
            @elseif ($approvalStatus === \App\Enums\ApprovalStatus::REJECTED)
                <div class="alert alert-error py-2 px-4 rounded-lg max-w-sm">
                    <x-icon name="lucide-x-circle" class="w-4 h-4 shrink-0" />
                    <div>
                        <div class="font-semibold text-sm">Project Rejected</div>
                        <p class="text-xs opacity-80">Review admin feedback, make changes, then resubmit.</p>
                        @if ($rejectionReason)
                            <div class="mt-2 p-2 rounded-md bg-base-300/30 text-xs">
                                <span class="font-semibold">Feedback:</span> {{ $rejectionReason }}
                            </div>
                        @endif
                    </div>
                </div>
            @elseif ($approvalStatus === \App\Enums\ApprovalStatus::APPROVED)
                <div class="badge badge-success gap-1.5 py-3 px-3 rounded-full text-xs font-medium">
                    <x-icon name="lucide-check-circle" class="w-3.5 h-3.5" />
                    Live
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-2 shrink-0">
                @if ($isDraft || $isRejected)
                    <div class="flex flex-col items-end gap-1">
                        <x-button
                            spinner
                            wire:click="sendToReview"
                            label="{{config('projects.auto_approve', false)? 'Publish' : 'Send to Review'}}"
                            class="btn-success btn-sm"
                            icon="lucide-send"
                            x-bind:disabled="hasUnsavedChanges"
                            x-bind:title="hasUnsavedChanges ? 'Save changes before submitting this project.' : null"
                        />
                    </div>
                @endif
                <x-button
                    spinner
                    wire:click="save"
                    label="Save Changes"
                    class="btn-primary btn-sm"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    icon="lucide-save"
                />
            </div>
        </div>
    </x-slot:header>

    <div>

        {{-- Unsaved Changes Banner --}}
        <div
            x-cloak
            x-show="hasUnsavedChanges"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="alert alert-warning mb-6"
            role="alert"
            aria-live="polite"
        >
            <x-icon name="lucide-alert-triangle" class="w-5 h-5 shrink-0" />
            <span>You have unsaved changes.</span>
        </div>

        <x-errors title="Oops!" description="Please, fix the following errors before proceeding."  icon="circle-x" class="mb-6"/>

        {{-- Dynamic section content --}}
        <div class="space-y-6">
            @if ($currentSection === 'general')
                @include('livewire.project-manager.sections.general')
            @elseif ($currentSection === 'description')
                @include('livewire.project-manager.sections.description')
            @elseif ($currentSection === 'tags')
                @include('livewire.project-manager.sections.tags')
            @elseif ($currentSection === 'links')
                @include('livewire.project-manager.sections.links')
            @elseif ($currentSection === 'members')
                @include('livewire.project-manager.sections.members')
            @elseif ($currentSection === 'danger')
                @include('livewire.project-manager.sections.danger')
            @endif
        </div>

    </div>

</x-platform.shell>

<script>
function unsavedChanges() {
    return {
        hasUnsavedChanges: false,
        isSaving: false,

        _beforeUnload: null,
        _navigateHandler: null,
        _saveSucceededHandler: null,
        _saveFailedHandler: null,
        _livewireCleanups: [],

        init() {
            this._attachNavigationGuards();
            this._attachSaveResultListeners();
            this._attachLivewireHooks();
        },

        markDirty() {
            if (!this.isSaving) {
                this.hasUnsavedChanges = true;
            }
        },

        cleanup() {
            window.removeEventListener('beforeunload', this._beforeUnload);
            document.removeEventListener('livewire:navigate', this._navigateHandler);
            window.removeEventListener('project-manager-save-succeeded', this._saveSucceededHandler);
            window.removeEventListener('project-manager-save-failed', this._saveFailedHandler);
            this._livewireCleanups.forEach(fn => fn());
        },

        _attachSaveResultListeners() {
            this._saveSucceededHandler = () => {
                this.hasUnsavedChanges = false;
                this.isSaving = false;
            };

            this._saveFailedHandler = () => {
                this.isSaving = false;
            };

            window.addEventListener('project-manager-save-succeeded', this._saveSucceededHandler);
            window.addEventListener('project-manager-save-failed', this._saveFailedHandler);
        },

        // ─── Navigation guards ────────────────────────────────────────────

        _attachNavigationGuards() {
            this._beforeUnload = (e) => {
                if (this.hasUnsavedChanges && !this.isSaving) {
                    e.preventDefault();
                    e.returnValue = ''; // Required for Chrome
                }
            };
            window.addEventListener('beforeunload', this._beforeUnload);

            this._navigateHandler = (e) => {
                if (this.hasUnsavedChanges && !this.isSaving) {
                    if (!confirm('You have unsaved changes. Leave anyway?')) {
                        e.preventDefault();
                    }
                }
            };
            document.addEventListener('livewire:navigate', this._navigateHandler);
        },

        // ─── Livewire lifecycle hooks ─────────────────────────────────────

        _attachLivewireHooks() {
            const wire = this.$wire;

            // Scope to this component instance and only the 'save' method.
            // Dirty state is cleared by explicit save success event from server.
            const cleanup = Livewire.hook('commit', ({ component, commit, succeed, fail }) => {
                if (component.id !== wire.__instance.id) return;

                const isSaveCall = commit.calls?.some(c => c.method === 'save');
                if (!isSaveCall) return;

                this.isSaving = true;

                succeed(() => {
                    this.isSaving = false;
                });

                fail(() => {
                    this.isSaving = false;
                });
            });

            this._livewireCleanups.push(cleanup);
        },
    };
}
</script>
