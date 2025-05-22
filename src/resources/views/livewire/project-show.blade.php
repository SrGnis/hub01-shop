<div class="w-full lg:w-10/12 m-auto py-6">

    <!-- Back Button and Actions -->
    <div class="mb-6 flex justify-between">
        <a href="{{ route('project-search', ['projectType' => $project->projectType]) }}" class="inline-flex items-center text-indigo-400 hover:text-indigo-300">
            @svg('lucide-arrow-left', 'w-5 h-5 mr-1')
            Back to Projects
        </a>
        @auth
            <div class="flex flex-col lg:flex-row space-y-2 lg:space-y-0 lg:space-x-2">
                @can('uploadVersion', $project)
                <a href="{{ route('project.version.create', ['projectType' => $project->projectType, 'project' => $project]) }}" class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md w-full lg:w-auto">
                    @svg('lucide-upload', 'w-4 h-4 mr-1')
                    <span class="whitespace-nowrap">Upload Version</span>
                </a>
                @endcan

                @can('update', $project)
                <a href="{{ route('project.edit', ['projectType' => $project->projectType, 'project' => $project]) }}" class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-md w-full lg:w-auto">
                    @svg('lucide-edit-3', 'w-4 h-4 mr-1')
                    <span class="whitespace-nowrap">Edit Project</span>
                </a>
                @endcan
            </div>
        @endauth
    </div>

    <!-- Inactive Project Notice -->
    @if($project->status === 'inactive')
    <div class="mb-6 bg-yellow-800 border-l-4 border-yellow-600 p-4 rounded-md">
        <div class="flex items-center">
            @svg('lucide-alert-triangle', 'w-6 h-6 text-yellow-400 mr-3')
            <div>
                <p class="text-yellow-300 font-medium">This project is currently inactive</p>
                <p class="text-yellow-200 text-sm mt-1">This project has been marked as inactive by its maintainers. It may not be actively maintained or supported.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column - Main Content -->
        <div class="lg:col-span-8">
            <!-- Project Card -->
            <div class="mb-6">
                <x-project-card :project="$project" />
            </div>

            <!-- Navigation Tabs -->
            <div class="flex border-b border-zinc-700 mb-6">
                <button wire:click="setActiveTab('description')"
                   class="px-4 py-2 {{ $activeTab === 'description' ? 'text-white border-b-2 border-indigo-500 font-medium' : 'text-gray-300 hover:text-white' }}">
                    Description
                </button>
                <button wire:click="setActiveTab('versions')"
                   class="px-4 py-2 {{ $activeTab === 'versions' ? 'text-white border-b-2 border-indigo-500 font-medium' : 'text-gray-300 hover:text-white' }}">
                    Versions
                </button>
                <button wire:click="setActiveTab('changelog')"
                   class="px-4 py-2 {{ $activeTab === 'changelog' ? 'text-white border-b-2 border-indigo-500 font-medium' : 'text-gray-300 hover:text-white' }}">
                    Changelog
                </button>
            </div>

            <!-- Tab Content -->
            @if ($activeTab === 'description')
                <livewire:project-show-description :project="$project" />
            @elseif ($activeTab === 'versions')
                <livewire:project-show-versions :project="$project" />
            @elseif ($activeTab === 'changelog')
                <livewire:project-show-changelog :project="$project" />
            @endif
        </div>

        <!-- Right Column - Sidebar -->
        <div class="lg:col-span-4">
            <!-- Versions Section -->
            <x-project-recent-versions :project="$project" />

            <!-- Creators Section -->
            <x-project-creators :project="$project" />

            <!-- Links Section -->
            <x-project-links :project="$project" />
        </div>
    </div>
</div>
