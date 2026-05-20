<x-platform.shell :title="'Manage Project: ' . $project->pretty_name">
    <x-slot:left>
        <x-project.manage-nav :projectType="$projectType" :project="$project" :current="$currentSection" />
    </x-slot:left>

    <x-slot:header>
        <div>
            {{-- Approval Status Banners --}}
            @if ($approvalStatus === \App\Enums\ApprovalStatus::DRAFT)
                <x-alert icon="lucide-file-edit" class="alert-info mb-6">
                    <div>
                        <div class="font-semibold">Project Draft</div>
                        <p class="text-sm mt-1">Your project is saved as a draft. When you're ready, click "Send to Review" to submit it for admin approval.</p>
                    </div>
                </x-alert>
            @elseif ($approvalStatus === \App\Enums\ApprovalStatus::PENDING)
                <x-alert icon="lucide-clock" class="alert-warning mb-6">
                    <div>
                        <div class="font-semibold">Project Under Review</div>
                        <p class="text-sm mt-1">Your project is currently pending admin approval. You cannot make edits, but it won't be visible to the public until approved.</p>
                    </div>
                </x-alert>
            @elseif ($approvalStatus === \App\Enums\ApprovalStatus::REJECTED)
                <x-alert icon="lucide-x-circle" class="alert-error mb-6">
                    <div>
                        <div class="font-semibold">Project Rejected</div>
                        <p class="text-sm mt-1">Your project was rejected by an admin. Please review the feedback below, make the necessary changes, and click "Send to Review" to resubmit.</p>
                        @if ($rejectionReason)
                            <div class="mt-3 p-3 rounded-lg bg-base-300">
                                <div class="text-xs font-semibold mb-1">Admin Feedback:</div>
                                <div class="text-sm">{{ $rejectionReason }}</div>
                            </div>
                        @endif
                    </div>
                </x-alert>
            @endif
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">
                    Manage Project: {{ $project->pretty_name }}
                </h1>
                <p class="text-sm text-gray-400 mt-1">
                    Manage your project settings and content
                </p>
            </div>
            <div class="flex gap-2">
                @if ($isDraft || $isRejected)
                    <x-button
                        spinner
                        wire:click="sendToReview"
                        label="Send to Review"
                        class="btn-success"
                        icon="lucide-send"
                    />
                @endif
                <x-button
                    spinner
                    wire:click="save"
                    label="Save Changes"
                    class="btn-primary"
                    wire:loading.attr="disabled"
                    wire:target="save"
                />
            </div>
        </div>
    </x-slot:header>

    <div x-data="unsavedChanges()" x-on:project-manager:dirty="markDirty()" x-on:mary-file-changed="markDirty()" @destroy="cleanup()">

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
