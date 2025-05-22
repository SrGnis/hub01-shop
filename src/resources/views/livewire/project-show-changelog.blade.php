<div class="bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Complete Changelog</h2>
        <div class="flex items-center space-x-2">
            <label for="perPageChangelog" class="text-sm">Show:</label>
            <x-forms.select :options="$perPageOptions" :property="'perPage'"></x-forms.select>
        </div>
    </div>

    <div class="changelog-list space-y-6">
        @foreach ($versions as $version)
            <div class="changelog-item">
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="text-lg font-semibold">{{ $version->name }} - {{ $version->version }}</h3>
                    <span class="text-xs {{ $version->bg_color_class }} px-2 py-1 rounded">{{ $version->display_name }}</span>
                </div>
                <div class="text-sm text-gray-400 mb-3">Released: {{ $version->release_date }}</div>
                <div class="prose prose-invert max-w-none border-l-2 border-zinc-700 pl-4">
                    {{ $version->changelog }}
                </div>
            </div>
        @endforeach
    </div>

    @if($versions->count() === 0)
        <div class="text-center py-8 text-gray-400">
            @svg('lucide-file-x', 'w-12 h-12 mx-auto mb-2')
            <p>No changelog entries available</p>
        </div>
    @endif

    <div class="mt-6">
        {{ $versions->links('vendor.livewire.tailwind') }}
    </div>
</div>
