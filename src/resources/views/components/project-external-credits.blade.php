@props(['project'])

<x-card title="External Credits" separator>
    @if($project->externalCredits->count() > 0)
        <div class="space-y-3">
            @foreach($project->externalCredits as $credit)
                <div class="pb-3 border-b border-base-content/10 last:border-b-0 last:pb-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-sm">{{ $credit->name }}</div>
                            <div class="text-xs text-base-content/60">{{ $credit->role }}</div>
                        </div>

                        @if($credit->url)
                            <a href="{{ $credit->url }}" target="_blank" rel="noopener noreferrer"
                                class="link link-primary text-xs whitespace-nowrap">
                                Visit
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-base-content/60 italic text-sm">No external credits</p>
    @endif
</x-card>
