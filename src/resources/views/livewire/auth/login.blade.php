<div>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-base-content">Sign In</h2>
        <p class="text-sm text-base-content/70 mt-2">Welcome back! Please sign in to your account.</p>
    </div>

    <x-form wire:submit="login">
        {{-- Email Field --}}
        <x-input 
            label="Email" 
            wire:model="email" 
            icon="mail" 
            placeholder="your@email.com"
            type="email"
            required 
        />

        {{-- Password Field --}}
        <x-password 
            label="Password" 
            wire:model="password" 
            placeholder="Enter your password"
            required 
        />

        {{-- Remember Me & Forgot Password --}}
        <div class="flex items-center justify-between">
            <x-checkbox 
                label="Remember me" 
                wire:model="remember" 
            />
            
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-primary hover:text-primary-focus">
                    Forgot your password?
                </a>
            @endif
        </div>

        {{-- Submit Button --}}
        <x-slot:actions>
            <x-button 
                label="Sign In" 
                type="submit" 
                icon="log-in" 
                class="btn-primary w-full" 
                spinner="login" 
            />
        </x-slot:actions>
    </x-form>

    {{-- Register Link --}}
    @if (Route::has('register'))
        <div class="mt-6 text-center">
            <p class="text-sm text-base-content/70">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-primary hover:text-primary-focus font-medium">
                    Sign up
                </a>
            </p>
        </div>
    @endif
</div>
