<div class="space-y-6">
    {{-- Project Deletion Section --}}
    @can('delete', $project)
        <div>
            <h2 class="text-xl font-bold mb-4 text-error">Delete Project</h2>
            <x-alert title="Warning" icon="lucide-alert-triangle"
                description="Deleting a project is permanent. It will be soft-deleted and visible to members for 14 days."
                class="alert-error mb-4" />
            <div class="space-y-4">
                <x-input label="Confirm by typing project name" wire:model="deleteConfirmation"
                    placeholder="{{ $project->name }}" />
                <div class="flex justify-end">
                    <x-button spinner wire:click="deleteProject" wire:confirm="Delete this project?"
                        label="Delete Project" class="btn-error" />
                </div>
            </div>
        </div>
    @endcan
</div>
