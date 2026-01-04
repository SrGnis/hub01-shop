<div>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-base-content">Two-Factor Authentication</h2>
        <p class="text-sm text-base-content/70 mt-2">
            @if($recovery)
                Please enter one of your emergency recovery codes.
            @else
                Please confirm access to your account by entering the authentication code provided by your authenticator application.
            @endif
        </p>
    </div>

    <x-form wire:submit="authenticate">
        @if($recovery)
            {{-- Recovery Code Field --}}
            <x-input 
                label="Recovery Code" 
                wire:model="recovery_code" 
                icon="key-round" 
                placeholder="Enter recovery code"
                hint="Use one of your emergency recovery codes"
                required 
            />
        @else
            {{-- Authentication Code Field --}}
            <x-input 
                label="Authentication Code" 
                wire:model="code" 
                icon="phone" 
                placeholder="000000"
                hint="Enter the 6-digit code from your authenticator app"
                maxlength="6"
                required 
            />
        @endif

        {{-- Toggle Recovery Mode --}}
        <div class="text-center">
            <button 
                type="button" 
                wire:click="toggleRecovery" 
                class="text-sm text-primary hover:text-primary-focus"
            >
                @if($recovery)
                    Use authentication code instead
                @else
                    Use a recovery code
                @endif
            </button>
        </div>

        {{-- Submit Button --}}
        <x-slot:actions>
            <x-button 
                label="Verify" 
                type="submit" 
                icon="shield-check" 
                class="btn-primary w-full" 
                spinner="authenticate" 
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
