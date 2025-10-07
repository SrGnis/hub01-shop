<div>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-base-content">Confirm Password</h2>
        <p class="text-sm text-base-content/70 mt-2">
            This is a secure area of the application. Please confirm your password before continuing.
        </p>
    </div>

    <x-form wire:submit="confirmPassword">
        {{-- Password Field --}}
        <x-password 
            label="Password" 
            wire:model="password" 
            placeholder="Enter your current password"
            hint="Confirm your identity to continue"
            required 
        />

        {{-- Submit Button --}}
        <x-slot:actions>
            <x-button 
                label="Confirm" 
                type="submit" 
                icon="o-shield-check" 
                class="btn-primary w-full" 
                spinner="confirmPassword" 
            />
        </x-slot:actions>
    </x-form>

    {{-- Cancel Link --}}
    <div class="mt-6 text-center">
        <a href="/" class="text-sm text-primary hover:text-primary-focus flex items-center justify-center gap-2">
            <x-icon name="o-arrow-left" class="w-4 h-4" />
            Cancel
        </a>
    </div>
</div>
