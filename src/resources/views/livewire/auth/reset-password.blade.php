<div>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-base-content">Reset Password</h2>
        <p class="text-sm text-base-content/70 mt-2">
            Please enter your new password below.
        </p>
    </div>

    <x-form wire:submit="resetPassword">
        {{-- Hidden Token Field --}}
        <input type="hidden" wire:model="token">

        {{-- Email Field (readonly) --}}
        <x-input 
            label="Email" 
            wire:model="email" 
            icon="mail" 
            type="email"
            readonly
            class="bg-base-200"
        />

        {{-- New Password Field --}}
        <x-password 
            label="New Password" 
            wire:model="password" 
            placeholder="Enter your new password"
            hint="Must be at least 8 characters"
            required 
        />

        {{-- Confirm Password Field --}}
        <x-password 
            label="Confirm New Password" 
            wire:model="password_confirmation" 
            placeholder="Confirm your new password"
            required 
        />

        {{-- Submit Button --}}
        <x-slot:actions>
            <x-button 
                label="Reset Password" 
                type="submit" 
                icon="key-round" 
                class="btn-primary w-full" 
                spinner="resetPassword" 
            />
        </x-slot:actions>
    </x-form>

    {{-- Back to Login Link --}}
    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm text-primary hover:text-primary-focus flex items-center justify-center gap-2">
            <x-icon name="arrow-left" class="w-4 h-4" />
            Back to Login
        </a>
    </div>
</div>
