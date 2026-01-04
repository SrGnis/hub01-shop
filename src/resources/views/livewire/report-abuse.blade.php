<div>
    <!-- Report Abuse Modal -->
    <x-modal wire:model="showModal" title="Report Abuse" separator>
        <x-slot:actions>
            <x-button label="Cancel" @click="closeModal" class="btn-ghost" />
            <x-button label="Submit Report" class="btn-primary" wire:click="submitReport" />
        </x-slot:actions>

        <div class="space-y-4">
            <p class="text-sm text-base-content/70">
                You are reporting the following {{ $reportedItemTypeLabel }}:
                <strong>{{ $reportedItemName }}</strong>
            </p>

            <x-textarea
                label="Reason for Report"
                wire:model="reason"
                placeholder="Please provide a detailed explanation of why you are reporting this content..."
                rows="5"
                hint="Minimum 10 characters, maximum 1000 characters"
                required
            />

            <div class="text-xs text-base-content/60">
                <p>By submitting this report, you acknowledge that:</p>
                <ul class="list-disc pl-4 mt-1 space-y-1">
                    <li>You are reporting in good faith</li>
                    <li>False reports may result in account restrictions</li>
                    <li>Our team will review your report and take appropriate action</li>
                </ul>
            </div>
        </div>
    </x-modal>
</div>
