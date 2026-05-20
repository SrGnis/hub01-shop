<x-card class="space-y-6" x-on:change="markDirty()">
    <div>
        <label class="text-sm font-medium mb-2 block">Tags</label>
        @foreach ($tagGroups as $tagGroup)
            <div class="mb-4">
                <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach ($tagGroup->tags as $tag)
                        <x-tag-check-item :tag="$tag" model="selectedTags" />
                    @endforeach
                </div>
            </div>
        @endforeach
        @error('selectedTags')
            <span class="text-error text-sm">{{ $message }}</span>
        @enderror
    </div>
</x-card>
