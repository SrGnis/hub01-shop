<div>
    <!-- Email Update Section -->
    <x-card class="max-w-2xl mb-6">
        <x-slot:title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-icon name="mail" class="w-5 h-5" />
                    <span>Email Address</span>
                </div>
                @if(!$pending_email_change)
                    <x-button
                        label="{{ $show_email_form ? 'Cancel' : 'Change Email' }}"
                        @click="$wire.toggleEmailForm()"
                        class="btn-sm btn-ghost"
                        icon="{{ $show_email_form ? 'x' : 'pencil' }}"
                    />
                @endif
            </div>
        </x-slot:title>

        @if($pending_email_change)
            <!-- Pending Email Change Status -->
            <div class="space-y-4">
                <div class="alert alert-info">
                    <x-icon name="info" class="w-5 h-5" />
                    <div>
                        <h3 class="font-semibold">Email Change in Progress</h3>
                        <p class="text-sm mt-1">
                            @if($pending_email_change->status === 'pending_authorization')
                                Step 1 of 2: Awaiting authorization from <strong>{{ $pending_email_change->old_email }}</strong>
                                <br>
                                <span class="text-xs">Expires: {{ $pending_email_change->authorization_expires_at->diffForHumans() }}</span>
                            @elseif($pending_email_change->status === 'pending_verification')
                                Step 2 of 2: Awaiting verification of <strong>{{ $pending_email_change->new_email }}</strong>
                                <br>
                                <span class="text-xs">Expires: {{ $pending_email_change->verification_expires_at->diffForHumans() }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <x-button
                    label="Cancel Email Change"
                    wire:click="cancelEmailChange"
                    icon="x"
                    class="btn-sm btn-outline"
                />
            </div>
        @elseif(!$show_email_form)
            <div class="text-base-content/70">
                {{ Auth::user()->email }}
            </div>
        @else
            <x-form wire:submit="requestEmailChange">
                <x-input
                    label="Current Email"
                    value="{{ Auth::user()->email }}"
                    disabled
                />

                <x-input
                    label="New Email"
                    wire:model="new_email"
                    type="email"
                    placeholder="your-new@email.com"
                    icon="mail"
                    required
                />

                <x-password
                    label="Current Password"
                    wire:model="current_password"
                    placeholder="Enter your password to confirm"
                    hint="Required for security"
                    required
                />

                <div class="alert alert-warning text-sm">
                    <x-icon name="alert-circle" class="w-4 h-4" />
                    <span>A verification email will be sent to both your current and new email addresses. You must authorize the change before it takes effect.</span>
                </div>

                <x-slot:actions>
                    <x-button
                        label="Cancel"
                        @click="$wire.toggleEmailForm()"
                        class="btn-ghost"
                    />
                    <x-button
                        label="Request Email Change"
                        type="submit"
                        icon="check"
                        class="btn-primary"
                        spinner="requestEmailChange"
                    />
                </x-slot:actions>
            </x-form>
        @endif
    </x-card>

    <!-- Password Update Section -->
    <x-card class="max-w-2xl">
        <x-slot:title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-icon name="lock" class="w-5 h-5" />
                    <span>Password</span>
                </div>
                @if(!$pending_password_change)
                    <x-button
                        label="{{ $show_password_form ? 'Cancel' : 'Change Password' }}"
                        @click="$wire.togglePasswordForm()"
                        class="btn-sm btn-ghost"
                        icon="{{ $show_password_form ? 'x' : 'pencil' }}"
                    />
                @endif
            </div>
        </x-slot:title>

        @if($pending_password_change)
            <!-- Pending Password Change Status -->
            <div class="space-y-4">
                <div class="alert alert-info">
                    <x-icon name="info" class="w-5 h-5" />
                    <div>
                        <h3 class="font-semibold">Password Change in Progress</h3>
                        <p class="text-sm mt-1">
                            Awaiting confirmation from your email address
                            <br>
                            <span class="text-xs">Expires: {{ $pending_password_change->expires_at->diffForHumans() }}</span>
                        </p>
                    </div>
                </div>

                <x-button
                    label="Cancel Password Change"
                    wire:click="cancelPasswordChange"
                    icon="x"
                    class="btn-sm btn-outline"
                />
            </div>
        @elseif(!$show_password_form)
            <div class="text-base-content/70">
                Last changed: {{ Auth::user()->updated_at->format('M d, Y') }}
            </div>
        @else
            <x-form wire:submit="requestPasswordChange">
                <x-password
                    label="Current Password"
                    wire:model="current_password"
                    placeholder="Enter your current password"
                    hint="Required for security"
                    required
                />

                <x-password
                    label="New Password"
                    wire:model="new_password"
                    placeholder="Create a strong password"
                    hint="Must be at least 8 characters"
                    required
                />

                <x-password
                    label="Confirm New Password"
                    wire:model="new_password_confirmation"
                    placeholder="Confirm your new password"
                    required
                />

                <div class="alert alert-warning text-sm">
                    <x-icon name="alert-circle" class="w-4 h-4" />
                    <span>A confirmation email will be sent to your email address. You must confirm the change before it takes effect.</span>
                </div>

                <x-slot:actions>
                    <x-button
                        label="Cancel"
                        @click="$wire.togglePasswordForm()"
                        class="btn-ghost"
                    />
                    <x-button
                        label="Request Password Change"
                        type="submit"
                        icon="check"
                        class="btn-primary"
                        spinner="requestPasswordChange"
                    />
                </x-slot:actions>
            </x-form>
        @endif
    </x-card>
</div>

