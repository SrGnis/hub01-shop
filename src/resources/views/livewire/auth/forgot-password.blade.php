<div>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-base-content">Forgot Password</h2>
        <p class="text-sm text-base-content/70 mt-2">
            No problem! Just let us know your email address and we'll send you a password reset link.
        </p>
    </div>

    @if($emailSent)
        {{-- Success State --}}
        <div class="text-center">
            <div class="mb-4">
                <x-icon name="circle-check" class="w-16 h-16 text-success mx-auto" />
            </div>
            <h3 class="text-lg font-semibold text-base-content mb-2">Email Sent!</h3>
            <p class="text-sm text-base-content/70 mb-6">
                We've sent a password reset link to <strong>{{ $email }}</strong>. 
                Please check your email and follow the instructions to reset your password.
            </p>
            <div class="space-y-3">
                <x-button 
                    label="Back to Login" 
                    link="{{ route('login') }}" 
                    icon="arrow-left" 
                    class="btn-primary w-full" 
                />
                <x-button 
                    label="Send Another Email" 
                    wire:click="$set('emailSent', false)" 
                    class="btn-outline w-full" 
                />
            </div>
        </div>
    @else
        {{-- Form State --}}
        <x-form wire:submit="sendResetLink">
            {{-- Email Field --}}
            <x-input 
                label="Email" 
                wire:model="email" 
                icon="mail" 
                placeholder="your@email.com"
                type="email"
                hint="Enter the email address associated with your account"
                required 
            />

            {{-- Submit Button --}}
            <x-slot:actions>
                <x-button 
                    label="Send Reset Link" 
                    type="submit" 
                    icon="send" 
                    class="btn-primary w-full" 
                    spinner="sendResetLink" 
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
    @endif
</div>
