@props(['options', 'property', 'searchable' => false])

<div x-id="['select']"
     x-data="{
        open: false,
        search: '',
        options: @js($options),
        selectedId: @entangle($property).live,
        get filteredOptions() {
            return this.search === ''
                ? this.options
                : this.options.filter(option =>
                    option.name.toLowerCase().includes(this.search.toLowerCase())
                );
        },
        get selectedOption() {
            if (!this.selectedId) return null;
            return this.options.find(option => String(option.id) === String(this.selectedId));
        },
        selectOption(option) {
            this.selectedId = option.id;
            this.open = false;
            this.search = '';
        },
        init() {
            this.$watch('open', (value) => {
                if (value) {
                    window.dispatchEvent(new CustomEvent('select-opened', {
                        detail: { id: $id('select') }
                    }));
                }
            });

            window.addEventListener('select-opened', (e) => {
                if (e.detail.id !== $id('select')) {
                    this.open = false;
                }
            });
        }
    }"
    {{ $attributes }}
>
    <div class="relative">
        <button :id="$id('select')"
                @click.stop="open = !open"
                type="button"
                class="relative cursor-pointer w-full text-normal border-gray-600 rounded-md bg-zinc-700 py-2 pl-3 pr-10 text-left ring-1 ring-inset ring-gray-600 focus:border-indigo-500 focus:ring-indigo-500"
                :aria-expanded="open"
                :aria-controls="$id('select-options')"
                role="combobox">
            <span class="flex items-center gap-2">
                <template x-if="selectedOption && selectedOption.icon">
                    <span x-html="document.querySelector(`[data-option-id='${selectedOption.id}'] svg`).outerHTML"></span>
                </template>
                <span class="block truncate" x-text="selectedOption ? selectedOption.name : 'Select an option'"></span>
            </span>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                @svg('lucide-chevrons-up-down', 'h-5 w-5')
            </span>
        </button>

        <div x-show="open"
            @click.away="open = false"
            x-cloak
            :id="$id('select-options')"
            role="listbox"
            class="absolute z-50 mt-1 max-h-60 w-max min-w-full overflow-auto rounded-md bg-zinc-700">
            @if($searchable)
            <div class="sticky top-0 z-10 bg-zinc-700 p-2">
                <input
                    :id="$id('select-search')"
                    x-model="search"
                    @click.stop
                    type="text"
                    class="w-full rounded-md bg-zinc-800 px-3 py-2 text-sm text-gray-300 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="Search..."
                >
            </div>
            @endif
            <ul class="max-h-60 overflow-auto">
                <template x-if="filteredOptions.length === 0">
                    <li class="py-2 px-3 text-gray-400 text-sm">No results found</li>
                </template>
                @foreach($options as $option)
                    <li wire:key="option-{{ $option['id'] }}"
                        x-show="filteredOptions.some(o => String(o.id) === String('{{ $option['id'] }}'))"
                        @click.stop="selectOption({id: '{{ $option['id'] }}', name: '{{ $option['name'] }}', icon: '{{ $option['icon'] ?? '' }}'})"
                        class="cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-zinc-500"
                        :class="{'bg-indigo-600 text-gray-300': selectedOption && String(selectedOption.id) === String('{{ $option['id'] }}')}"
                        :id="$id('select-option', '{{ $option['id'] }}')"
                        role="option"
                        :aria-selected="selectedOption && String(selectedOption.id) === String('{{ $option['id'] }}')"
                        data-option-id="{{ $option['id'] }}">
                        <div class="flex items-center gap-2">
                            @if(isset($option['icon']))
                                <span>
                                    @svg($option['icon'], 'h-5 w-5')
                                </span>
                            @endif
                            <span class="font-normal block">{{ $option['name'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
