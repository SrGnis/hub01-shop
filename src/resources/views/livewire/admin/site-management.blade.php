<div class="w-full lg:w-10/12 m-auto py-6">
    <x-card>
        <h1 class="text-2xl font-bold mb-6">Site Management</h1>

        <x-tabs wire:model="activeTab">
            <x-tab name="project-types" label="Project Types" icon="lucide-package">
                <livewire:admin.project-type-management />
            </x-tab>

            <x-tab name="tag-groups" label="Tag Groups" icon="lucide-folder">
                <livewire:admin.tag-group-management />
            </x-tab>

            <x-tab name="tags" label="Tags" icon="lucide-tag">
                <livewire:admin.tag-management />
            </x-tab>

            <x-tab name="version-tag-groups" label="Version Tag Groups" icon="lucide-folder-git">
                <livewire:admin.version-tag-group-management />
            </x-tab>

            <x-tab name="version-tags" label="Version Tags" icon="lucide-git-branch">
                <livewire:admin.version-tag-management />
            </x-tab>
        </x-tabs>
    </x-card>
</div>
