<x-card class="space-y-6" x-on:input="markDirty()" x-on:change="markDirty()">
    {{-- Links --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-input label="Website URL" wire:model="website" type="url" icon="globe"
            placeholder="https://example.com" />
        <x-input label="Issues URL" wire:model="issues" type="url" icon="bug"
            placeholder="https://github.com/user/repo/issues" />
        <x-input label="Source Code URL" wire:model="source" type="url" icon="code"
            placeholder="https://github.com/user/repo" />
    </div>

    {{-- External Credits --}}
    <div class="space-y-4 mt-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">External Credits</h2>
                <p class="text-sm text-gray-400">Credit external collaborators that are not project members.</p>
            </div>
            <x-button type="button" wire:click="addExternalCredit" x-on:click="markDirty()" label="Add Credit" icon="lucide-plus"
                class="btn-sm btn-outline" />
        </div>

        @foreach ($externalCredits as $index => $credit)
            <div wire:key="external-credit-{{ $index }}" class="rounded-lg border border-base-300 p-4 space-y-3">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                    <div class="lg:col-span-4">
                        <x-input label="Name" wire:model="externalCredits.{{ $index }}.name"
                            placeholder="Contributor name" />
                    </div>

                    <div class="lg:col-span-4">
                        <x-input label="Role" wire:model="externalCredits.{{ $index }}.role"
                            placeholder="Composer, Artist, Voice Actor..." />
                    </div>

                    <div class="lg:col-span-4">
                        <x-input label="URL (optional)" wire:model="externalCredits.{{ $index }}.url" type="url"
                            placeholder="https://example.com/profile" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-button type="button" wire:click="removeExternalCredit({{ $index }})" x-on:click="markDirty()" label="Remove"
                        icon="lucide-trash-2" class="btn-sm btn-error btn-outline" />
                </div>
            </div>
        @endforeach
    </div>
</x-card>
