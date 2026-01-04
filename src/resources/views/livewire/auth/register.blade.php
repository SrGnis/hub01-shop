<div>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-base-content">Create Account</h2>
        <p class="text-sm text-base-content/70 mt-2">Join us today! Please fill in your information to get started.</p>
    </div>

    <x-form wire:submit="register">
        {{-- Name Field --}}
        <x-input
            label="Username"
            wire:model="name"
            icon="user"
            placeholder="JohnDoe123"
            required
        />

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
            placeholder="Create a strong password"
            hint="Must be at least 8 characters"
            required
        />

        {{-- Password Confirmation Field --}}
        <x-password
            label="Confirm Password"
            wire:model="password_confirmation"
            placeholder="Confirm your password"
            required
        />

        {{-- Terms and Conditions --}}
        <x-checkbox
            label="I agree to the Terms of Service and Privacy Policy"
            wire:model="terms"
            required
        >
            <x-slot:label>
                I agree to the
                <a href="{{ route('page.show', 'terms-of-service') }}" class="text-primary hover:text-primary-focus">Terms of Service</a>
                and
                <a href="{{ route('page.show', 'privacy-policy') }}" class="text-primary hover:text-primary-focus">Privacy Policy</a>
            </x-slot:label>
        </x-checkbox>

        {{-- Submit Button --}}
        <x-slot:actions>
            <x-button
                label="Create Account"
                type="submit"
                icon="user-plus"
                class="btn-primary w-full"
                spinner="register"
            />
        </x-slot:actions>
    </x-form>

    {{-- Login Link --}}
    <div class="mt-6 text-center">
        <p class="text-sm text-base-content/70">
            Already have an account?
            <a href="{{ route('login') }}" class="text-primary hover:text-primary-focus font-medium">
                Sign in
            </a>
        </p>
    </div>
</div>
