<x-card x-data="{ mode: 'code' }" class="space-y-6" x-on:input="markDirty()" x-on:change="markDirty()">
    <div class="flex justify-between items-center mb-2">
        <label class="text-sm font-medium">Description</label>
        <div class="join">
            <button type="button" @click="mode = 'code'"
                :class="{ 'join-item btn-active': mode === 'code' }" class="join-item btn btn-sm">
                <x-icon name="lucide-code" class="w-4 h-4" /> Code
            </button>
            <button type="button" @click="$wire.refreshMarkdown().then(() => mode = 'preview')"
                :class="{ 'join-item btn-active': mode === 'preview' }" class="join-item btn btn-sm">
                <x-icon name="lucide-eye" class="w-4 h-4" /> Preview
            </button>
        </div>
    </div>
    <div wire:loading.remove wire:target="refreshMarkdown" x-show="mode === 'code'">
        <x-code wire:model="description" height="300px" language="markdown" hint="Markdown" wrap=1
            required />
    </div>
    <div wire:loading.flex wire:target="refreshMarkdown"
        class="bg-base-200 rounded-lg p-4 min-h-[242px] w-full flex items-center justify-center">
        <span class="loading loading-spinner w-10 h-10"></span>
    </div>
    <div x-show="mode === 'preview'" x-cloak class="bg-base-200 rounded-lg p-4 min-h-[242px]">
        <x-markdown class="prose prose-invert max-w-none" flavor="github">{{ $description }}</x-markdown>
    </div>
</x-card>
