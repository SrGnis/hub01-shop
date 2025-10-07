<div>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-base-content">Verify Your Email</h2>
        <p class="text-sm text-base-content/70 mt-2">
            Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
        </p>
    </div>

    <div class="text-center">
        <div class="mb-6">
            <x-icon name="o-envelope" class="w-16 h-16 text-primary mx-auto" />
        </div>

        <p class="text-sm text-base-content/70 mb-6">
            We've sent a verification email to <strong>{{ Auth::user()->email }}</strong>. 
            Please check your inbox and click the verification link.
        </p>

        <div class="space-y-3">
            <x-button 
                label="Resend Verification Email" 
                wire:click="resendVerification" 
                icon="o-paper-airplane" 
                class="btn-primary w-full" 
                spinner="resendVerification" 
            />

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button 
                    label="Logout" 
                    type="submit" 
                    icon="o-arrow-right-on-rectangle" 
                    class="btn-outline w-full" 
                />
            </form>
        </div>
    </div>
</div>
