<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold mb-6">Site Management</h1>

        <!-- Tabs -->
        <div class="border-b border-zinc-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="setActiveTab('project-types')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'project-types' ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300' }}">
                    Project Types
                </button>
                <button wire:click="setActiveTab('tags')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'tags' ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300' }}">
                    Tags
                </button>
                <button wire:click="setActiveTab('tag-groups')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'tag-groups' ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300' }}">
                    Tag Groups
                </button>
                <button wire:click="setActiveTab('version-tags')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'version-tags' ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300' }}">
                    Version Tags
                </button>
                <button wire:click="setActiveTab('version-tag-groups')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'version-tag-groups' ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300' }}">
                    Version Tag Groups
                </button>
            </nav>
        </div>

        <!-- Project Types Tab -->
        @if($activeTab === 'project-types')
            @livewire('admin.project-type-management')
        @endif

        <!-- Tags Tab -->
        @if($activeTab === 'tags')
            @livewire('admin.tag-management')
        @endif

        <!-- Tag Groups Tab -->
        @if($activeTab === 'tag-groups')
            @livewire('admin.tag-group-management')
        @endif

        <!-- Version Tags Tab -->
        @if($activeTab === 'version-tags')
            @livewire('admin.version-tag-management')
        @endif

        <!-- Version Tag Groups Tab -->
        @if($activeTab === 'version-tag-groups')
            @livewire('admin.version-tag-group-management')
        @endif
    </div>
</div>
