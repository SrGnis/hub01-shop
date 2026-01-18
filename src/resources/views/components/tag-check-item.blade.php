@props([
    'tag',
    'model',
    'live' => false,
    ])

<div class="flex flex-col" x-data="{ expanded: false, live: @js($live) }" wire:ignore>
    {{-- Main tag checkbox --}}
    <x-checkbox
        wire:key="tag-{{$model}}-{{ $tag->id }}"
        wire:model="{{ $model }}"
        value="{{ $tag->id }}"
        class="text-sm main-tag-checkbox"
        data-tag-id="{{$model}}-{{ $tag->id }}"
        @change="handleMainTagChange($el, live, $wire)"
    >
        <x-slot:label>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    @if($tag->icon)
                        <x-icon :name="$tag->icon" class="w-4 h-4" />
                    @endif
                    {{ $tag->name }}
                </div>
                @if($tag->children->count() > 0)
                    <x-button
                        type="button"
                        @click="expanded = !expanded"
                        class="btn-ghost btn-xs btn-circle"
                    >
                        <x-icon
                            name="lucide-chevron-right"
                            class="w-3 h-3"
                            x-show="!expanded"
                        />
                        <x-icon
                            name="lucide-chevron-down"
                            class="w-3 h-3"
                            x-show="expanded"
                        />
                    </x-button>
                @endif
            </div>
        </x-slot:label>
    </x-checkbox>

    {{-- Sub-tags (shown when expanded) --}}
    @if($tag->children->count() > 0)
        <div x-show="expanded" class="ml-6 flex flex-col gap-1 mt-1">
            @foreach($tag->children as $subTag)
                <x-checkbox
                    wire:key="sub-tag-{{$model}}-{{ $subTag->id }}"
                    wire:model="{{ $model }}"
                    value="{{ $subTag->id }}"
                    class="text-sm sub-tag-checkbox"
                    data-tag-id="{{ $subTag->id }}"
                    data-parent-id="{{$model}}-{{ $tag->id }}"
                    @change="handleSubTagChange($el, live, $wire)"
                >
                    <x-slot:label class="flex items-center gap-2">
                        <div class="flex items-center gap-2">
                            @if($subTag->icon)
                                <x-icon :name="$subTag->icon" class="w-3 h-3" />
                            @endif
                            <span class="text-xs">{{ $subTag->name }}</span>
                        </div>
                    </x-slot:label>
                </x-checkbox>
            @endforeach
        </div>
    @endif
</div>
