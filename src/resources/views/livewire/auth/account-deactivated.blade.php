<div>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-base-content">Account Deactivated</h2>
        <p class="text-sm text-base-content/70 mt-2">
            Your account has been deactivated and you can no longer access the platform.
        </p>
    </div>

    <div class="text-center">
        <div class="mb-6">
            <x-icon name="lucide-user-x" class="w-16 h-16 text-error mx-auto" />
        </div>

        <p class="text-sm text-base-content/70 mb-6">
            If you believe this was done in error or would like to reactivate your account, 
            please contact support for assistance.
        </p>

        <div class="space-y-3">
            <x-button 
                label="Return to Home" 
                link="/" 
                icon="lucide-home" 
                class="btn-primary w-full" 
            />
        </div>
    </div>
</div>

