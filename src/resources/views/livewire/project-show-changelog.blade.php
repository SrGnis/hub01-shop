<x-card>
    <x-slot:title class="flex justify-between items-center">
        <span>Complete Changelog</span>
        <div class="flex items-center gap-2">
            <label for="perPage" class="text-sm">Show:</label>
            <x-select
                wire:model.live="perPage"
                :options="[
                    ['id' => 10, 'name' => '10'],
                    ['id' => 25, 'name' => '25'],
                    ['id' => 50, 'name' => '50'],
                    ['id' => 100, 'name' => '100'],
                ]"
                option-key="id"
                option-value="id"
                option-label="name"
                class="select-sm"
            />
        </div>
    </x-slot:title>

    @if($versions->count() > 0)
        <div class="space-y-6">
            @foreach ($versions as $version)
                <div class="pb-6 border-b border-base-content/10 last:border-b-0 last:pb-0">
                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="text-lg font-semibold">{{ $version->name }} - {{ $version->version }}</h3>
                        <x-badge :value="$version->display_name" class="badge-sm badge-soft" />
                    </div>
                    <div class="text-sm text-base-content/60 mb-3">
                        Released: {{ $version->release_date->format('M d, Y') }}
                    </div>
                    <div class="prose prose-sm max-w-none dark:prose-invert border-l-2 border-base-content/20 pl-4">
                        {!! $version->changelog !!}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $versions->links('vendor.livewire.tailwind') }}
        </div>
    @else
        <div class="text-center py-12">
            <x-icon name="file-x" class="w-12 h-12 mx-auto text-base-content/30 mb-2" />
            <p class="text-base-content/60">No changelog entries available</p>
        </div>
    @endif
</x-card>

