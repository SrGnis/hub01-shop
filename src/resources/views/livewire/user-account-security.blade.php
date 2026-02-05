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
    <x-card class="max-w-2xl mb-6">
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

    <!-- API Token Management Section -->
    <x-card class="max-w-4xl">
        <x-slot:title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-icon name="key" class="w-5 h-5" />
                    <span>API Tokens</span>
                </div>
                <x-button
                    label="Create Token"
                    wire:click="openTokenModal"
                    icon="plus"
                    class="btn-sm btn-primary"
                />
            </div>
        </x-slot:title>

        <div class="space-y-4">
            <p class="text-sm text-base-content/70">
                Manage your API tokens for accessing the Hub01 Shop API.
            </p>

            @if(count($this->tokens) > 0)
                <x-table
                    :headers="[
                        ['key' => 'name', 'label' => 'Name'],
                        ['key' => 'created_at', 'label' => 'Created', 'format' => ['date', 'Y-m-d']],
                        ['key' => 'expires_at', 'label' => 'Expires'],
                        ['key' => 'last_used_at', 'label' => 'Last Used'],
                    ]"
                    :rows="$this->tokens"
                    show-empty-text
                >
                    @scope('cell_expires_at', $token)
                        @php
                            $expired_class = 'text-success';
                            if($token->expires_at && $token->expires_at->isPast()) {
                                $expired_class = 'text-error';
                            } elseif($token->expires_at && now()->diffInDays($token->expires_at) < 7) {
                                $expired_class = 'text-warning';
                            }
                        @endphp
                        @if($token->expires_at)
                            <span class="{{ $expired_class }}">
                                {{ $token->expires_at->format('Y-m-d') }}
                            </span>
                        @else
                            <span class="text-base-content/50">Never</span>
                        @endif
                    @endscope

                    @scope('cell_last_used_at', $token)
                        @if($token->last_used_at)
                            {{ $token->last_used_at->diffForHumans() }}
                        @else
                            <span class="text-base-content/50">Never</span>
                        @endif
                    @endscope

                    @scope('actions', $token)
                        <div class="flex gap-2 justify-end">
                            @if ($token->expires_at)
                                <x-button
                                    icon="refresh-cw"
                                    class="btn-ghost btn-xs"
                                    tooltip="Renew"
                                    wire:click="renewToken({{ $token->id }})"
                                />
                            @endif
                            <x-button
                                icon="trash-2"
                                class="btn-ghost btn-xs text-error"
                                tooltip="Revoke"
                                wire:click="confirmRevokeToken({{ $token->id }})"
                            />
                        </div>
                    @endscope
                </x-table>
            @else
                <div class="text-center py-8 text-base-content">
                    <x-icon name="key" class="w-12 h-12 mx-auto mb-3 opacity-30" />
                    <p class="text-base-content/70">No API tokens created yet.</p>
                </div>
            @endif
        </div>
    </x-card>

    <!-- Create/Edit Token Modal -->
    <x-modal
        wire:model="show_token_modal"
        title="{{ $editing_token_id ? 'Renew Token' : 'Create API Token' }}"
        separator
    >
        <x-form wire:submit="{{ $editing_token_id ? 'updateTokenExpiration' : 'createToken' }}">
            @if(!$editing_token_id)
                <x-input
                    label="Token Name"
                    wire:model="token_name"
                    placeholder="e.g., My Application Token"
                    hint="Give your token a descriptive name"
                    required
                />
            @else
                <div class="alert alert-info mb-4">
                    <x-icon name="info" class="w-4 h-4" />
                    <span>Renewing token: <strong>{{ $editing_token_name }}</strong></span>
                </div>
            @endif

            @if (!$editing_token_id)
                <x-datetime
                    label="Expiration Date"
                    wire:model="token_expiration"
                    icon="calendar"
                    hint="Leave blank to create a token that never expires"
                />
            @else
                <x-datetime
                    label="Expiration Date"
                    wire:model="token_expiration"
                    icon="calendar"
                    hint="Select a new expiration date for this token"
                    required
                />
            @endif

            <x-slot:actions>
                <x-button
                    label="Cancel"
                    type="button"
                    wire:click="closeTokenModal"
                    class="btn-ghost"
                />
                <x-button
                    label="{{ $editing_token_id ? 'Update Expiration' : 'Create Token' }}"
                    type="submit"
                    icon="check"
                    class="btn-primary"
                    spinner="{{ $editing_token_id ? 'updateTokenExpiration' : 'createToken' }}"
                />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Token Display Modal (One-time view) -->
    <x-modal
        wire:model="show_token_display"
        title="API Token Created"
        separator
        persistent
    >
        <div class="space-y-4">
            <div class="alert alert-warning">
                <x-icon name="alert-triangle" class="w-5 h-5" />
                <div>
                    <h4 class="font-semibold">Copy your token now!</h4>
                    <p class="text-sm">This token will only be shown once. Store it securely.</p>
                </div>
            </div>

            <x-input
                label="Your API Token"
                value="{{ $displayed_token }}"
                readonly
                class="font-mono text-sm"
            />

            <div class="text-sm text-base-content/70">
                <p>Use this token in the Authorization header:</p>
                <code class="block bg-base-200 p-2 rounded mt-2 font-mono text-xs">
                    Authorization: Bearer {{ substr($displayed_token, 0, 8) }}...
                </code>
            </div>
        </div>

        <x-slot:actions>
            <x-button
                label="I've copied my token"
                wire:click="closeTokenDisplay"
                class="btn-primary"
                icon="check"
            />
        </x-slot:actions>
    </x-modal>

    <!-- Revoke Confirmation Modal -->
    <x-modal wire:model="show_revoke_confirmation" title="Revoke Token" separator>
        <div class="space-y-4">
            <div class="alert alert-error">
                <x-icon name="alert-triangle" class="w-5 h-5" />
                <div>
                    <h4 class="font-semibold">Are you sure?</h4>
                    <p class="text-sm">This action cannot be undone. Any applications using this token will stop working immediately.</p>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button
                label="Cancel"
                onclick="document.getElementById('revoke-token-confirmation').close()"
                class="btn-ghost"
            />
            <x-button
                label="Revoke Token"
                wire:click="revokeToken({{ $editing_token_id }})"
                class="btn-error"
                icon="trash-2"
                spinner="revokeToken"
            />
        </x-slot:actions>
    </x-modal>
</div>
