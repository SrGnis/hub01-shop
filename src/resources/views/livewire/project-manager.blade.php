<x-platform.shell :title="'Manage Project: ' . $project->pretty_name">
    <x-slot:left>
        <x-project.manage-nav :projectType="$projectType" :project="$project" :current="$currentSection" />
    </x-slot:left>

    <x-slot:header>
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
                    <x-button spinner wire:click="sendToReview" label="Send to Review" class="btn-success" icon="lucide-send" />
                @endif
                <x-button spinner wire:click="save" label="Save Changes" class="btn-primary" />
            </div>
        </div>
    </x-slot:header>

    <div>
        {{-- Approval Status Banner --}
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
            @elseif($approvalStatus === \App\Enums\ApprovalStatus::REJECTED)
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
        @endif

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
