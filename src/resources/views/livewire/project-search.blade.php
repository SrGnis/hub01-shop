<div x-data="{ open: false }" x-effect="open ? document.body.classList.add('overflow-hidden') : document.body.classList.remove('overflow-hidden')">
    <div class="w-full lg:w-10/12 m-auto grid grid-cols-1 lg:grid-cols-12 gap-4 py-6 max-w-[1080px]" :class="{ 'pointer-events-none': open }">
        <!-- Only the modal should be interactive when open -->
        <div x-show="open" class="pointer-events-none" style="display: none;"></div>
        <!-- Mobile Filter Button - Only visible on mobile -->
        <div class="block lg:hidden w-full mb-4">
            <button @click="open = true" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded flex items-center justify-center">
                @svg('lucide-list-filter', 'h-5 w-5 mr-2')
                Filter & Sort
            </button>
        </div>

        <!-- Desktop Filters - Visible only on desktop -->
        <div id="filters" class="hidden lg:flex flex-col w-full lg:col-span-3">
            <div class="tag-filter bg-zinc-800 text-gray-300 p-4 rounded-md">
                <div class="text-xl font-bold text-center mb-4">
                    Tag Filter
                </div>

                <!-- Project Tags -->
                <div class="mb-6">
                    @foreach ($this->tagGroups as $tagGroup)
                        <div class="mb-4">
                            <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                            @foreach ($tagGroup->tags as $tag)
                                <div class="mod-card flex flex-row gap-2 items-center ml-2 mb-1">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="selectedTags" value="{{ $tag->id }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-zinc-300 rounded">
                                        @svg($tag->icon, 'ml-2 w-5 h-5')
                                        <span class="ml-2">{{ $tag->name }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <!-- Version Tags -->
                <div>
                    @foreach ($this->versionTagGroups as $tagGroup)
                        <div class="mb-4">
                            <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                            @foreach ($tagGroup->tags as $tag)
                                <div class="mod-card flex flex-row gap-2 items-center ml-2 mb-1">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="selectedVersionTags" value="{{ $tag->id }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-zinc-300 rounded">
                                        @svg($tag->icon, 'ml-2 w-5 h-5')
                                        <span class="ml-2">{{ $tag->name }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Mobile Filter Modal -->
        <div x-show="open"
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center lg:block lg:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" @click="open = false"></div>
                </div>
                <div class="inline-block align-bottom bg-zinc-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all lg:my-8 lg:align-middle lg:max-w-xl lg:max-w-2xl w-full mx-4 pointer-events-auto">
                    <div class="bg-zinc-800 px-4 pt-5 pb-4 lg:p-6 lg:pb-4">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-2xl font-bold text-gray-300">Filter & Sort Projects</h3>
                            <button @click="open = false" class="text-gray-300 hover:text-white">
                                @svg('lucide-x', 'h-6 w-6')
                            </button>
                        </div>

                        <!-- Sorting Options -->
                        <div class="mb-6 border-b border-zinc-700 pb-6">
                            <h4 class="text-xl font-semibold text-gray-300 mb-4">Sort Options</h4>
                            <div class="space-y-4">
                                <!-- Order By -->
                                <div class="flex flex-col gap-2">
                                    <label for="orderBy" class="text-sm font-medium text-gray-300">Order by:</label>
                                    <x-forms.select :options="$orderOptions" :property="'orderBy'"></x-forms.select>
                                </div>
                                <!-- Direction -->
                                <div class="flex flex-col gap-2">
                                    <label for="orderDirection" class="text-sm font-medium text-gray-300">Direction:</label>
                                    <x-forms.select :options="$directionOptions" :property="'orderDirection'"></x-forms.select>
                                </div>
                                <!-- Results Per Page -->
                                <div class="flex flex-col gap-2">
                                    <label for="resultsPerPage" class="text-sm font-medium text-gray-300">Results per page:</label>
                                    <x-forms.select :options="$perPageOptions" :property="'resultsPerPage'"></x-forms.select>
                                </div>
                            </div>
                        </div>

                        <!-- Tag Filter -->
                        <div class="tag-filter text-gray-300">
                            <!-- Project Tags -->
                            <h4 class="text-xl font-semibold text-gray-300 mb-4">Project Tags</h4>
                            @foreach ($this->tagGroups as $tagGroup)
                                <div class="mb-4">
                                    <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 lg:grid-cols-3 gap-2">
                                        @foreach ($tagGroup->tags as $tag)
                                            <div class="mod-card flex flex-row gap-2 items-center py-2">
                                                <input type="checkbox" wire:model.live="selectedTags" value="{{ $tag->id }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-zinc-300 rounded">
                                                @svg($tag->icon, 'w-5 h-5 ml-1')
                                                <span class="ml-1">{{ $tag->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <!-- Version Tags -->
                            <h4 class="text-xl font-semibold text-gray-300 mb-4 mt-6">Version Tags</h4>
                            @foreach ($this->versionTagGroups as $tagGroup)
                                <div class="mb-4">
                                    <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 lg:grid-cols-3 gap-2">
                                        @foreach ($tagGroup->tags as $tag)
                                            <div class="mod-card flex flex-row gap-2 items-center py-2">
                                                <input type="checkbox" wire:model.live="selectedVersionTags" value="{{ $tag->id }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-zinc-300 rounded">
                                                @svg($tag->icon, 'w-5 h-5 ml-1')
                                                <span class="ml-1">{{ $tag->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-zinc-700 px-4 py-4 lg:px-6 lg:flex lg:flex-row-reverse">
                        <button @click="open = false" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-3 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 lg:ml-3 lg:w-auto lg:text-lg">
                            @svg('lucide-list-filter', 'h-5 w-5 mr-2')
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="lg:col-span-9">
            <div class="mod-list flex flex-col gap-4 w-9/10 lg:w-full m-auto">
                <input type="text" wire:model.live.debounce.500ms="search" class="bg-zinc-800 text-gray-300 p-2 w-full rounded-md" placeholder="Search projects...">

                <!-- Order and Results Per Page Options (Desktop Only) -->
                <div class="hidden lg:block bg-zinc-800 text-gray-300 p-2 rounded-md">
                    <div class="flex flex-row justify-between gap-2">
                        <!-- Order Options Group -->
                        <div class="flex flex-row gap-2 items-center">
                            <!-- Order By -->
                            <div class="flex flex-row gap-2 items-center">
                                <label for="orderBy" class="text-sm font-medium">Order by:</label>
                                <x-forms.select :options="$orderOptions" :property="'orderBy'"></x-forms.select>
                            </div>
                            <!-- Direction -->
                            <div class="flex flex-row gap-2 items-center ml-2">
                                <label for="orderDirection" class="text-sm font-medium">Direction:</label>
                                <x-forms.select :options="$directionOptions" :property="'orderDirection'"></x-forms.select>
                            </div>
                        </div>
                        <!-- Results Per Page -->
                        <div class="flex flex-row gap-2 items-center">
                            <label for="resultsPerPage" class="text-sm font-medium">Results per page:</label>
                            <x-forms.select :options="$perPageOptions" :property="'resultsPerPage'"></x-forms.select>
                        </div>
                    </div>
                </div>

                <div id="search-results">
                    {{ $this->projects->onEachSide(1)->links('vendor.livewire.tailwind')->with('scrollTo', false) }}
                    @foreach ($this->projects as $project)
                        <x-project-card :project="$project" />
                    @endforeach
                    {{ $this->projects->onEachSide(1)->links('vendor.livewire.tailwind')->with('scrollTo', '#search-results') }}
                </div>
            </div>
        </div>
    </div>
</div>
