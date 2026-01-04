<div>
    <x-header title="{{ $user->name }}" separator />

    <!-- Responsive Grid Container -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Account Info Section -->
        <div>
            <x-header title="Account Info" separator />
            <x-card>
                <x-form wire:submit="save">
                    <!-- Avatar Section -->
                    <div class="mb-8">
                        <label class="block text-sm font-semibold mb-4">Profile Picture</label>
                        <div class="flex items-end gap-6">
                            <!-- Avatar Preview -->
                            <div class="flex-shrink-0">
                                <div class="w-24 h-24 rounded-full bg-base-200 flex items-center justify-center overflow-hidden border-2 border-base-300">
                                    @if($avatar)
                                        <img src="{{ $avatar->temporaryUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                    @elseif($user->avatar)
                                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                    @else
                                        <x-icon name="user" class="w-12 h-12 text-base-content/40" />
                                    @endif
                                </div>
                            </div>
                            <!-- Upload Input -->
                            <div class="flex-1">
                                <x-file
                                    wire:model="avatar"
                                    label="Upload new picture"
                                    hint="JPG, PNG, GIF up to 2MB"
                                    accept="image/jpeg,image/png,image/gif"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Bio Field -->
                    <x-textarea
                        label="Bio"
                        wire:model="bio"
                        placeholder="Tell us about yourself..."
                        :max-length="125"
                        :alpine-char-count="true"
                        rows="4"
                    />

                    <!-- Actions -->
                    <x-slot:actions>
                        <x-button
                            label="Cancel"
                            link="{{ route('user.profile', auth()->user()) }}"
                            class="btn-ghost"
                        />
                        <x-button
                            label="Save Changes"
                            type="submit"
                            icon="check"
                            class="btn-primary"
                            spinner="save"
                        />
                    </x-slot:actions>
                </x-form>
            </x-card>
        </div>

        <!-- Account Security Section -->
        <div>
            <x-header title="Account Security" separator />
            <livewire:user-account-security />
        </div>
    </div>
</div>

