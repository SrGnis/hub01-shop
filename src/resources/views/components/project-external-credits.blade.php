@props(['project'])

@if($project->externalCredits->count() > 0)
<x-card title="Credits" separator>
    <div class="space-y-3">
        @foreach($project->externalCredits as $credit)
            <div class="pb-3 border-b border-base-content/10 last:border-b-0 last:pb-0">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-sm">{{ $credit->name }}</div>
                        <div class="text-xs text-base-content/60">{{ $credit->role }}</div>
                    </div>

                    @if($credit->url)
                        <x-button tooltip="{{ $credit->url }}" link="{{ $credit->url }}" external
                            class="btn-ghost btn-circle" icon="external-link">
                        </x-button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-card>
@endif
