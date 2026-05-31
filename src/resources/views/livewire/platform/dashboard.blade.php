<x-platform.shell>

    <x-slot:header>
        <x-header
            title="Dashboard"
            icon="layout-panel-left"
            icon-classes="w-6 h-6"
            subtitle="Overview of your profile, latest notifications, and quick analytics." />
    </x-slot:header>

    <x-slot:left>
        <x-platform.section-nav current="dashboard" />
    </x-slot:left>

    <x-card>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <x-avatar
                placeholder="{{ strtoupper(substr($this->profile['name'], 0, 1)) }}"
                placeholder-text-class="text-2xl font-bold"
                placeholder-bg-class="bg-primary text-primary-content"
                class="!w-20"
                image="{{ $this->profile['avatar_url'] }}"
            >
                <x-slot:title class="text-xl !font-bold pl-2">
                    {{ $this->profile['name'] }}
                </x-slot:title>

                <x-slot:subtitle class="mt-1">
                    <x-button
                        link="{{ $this->profile['profile_url'] }}"
                        label="View profile"
                        icon-right="lucide-square-arrow-out-up-right"
                        class="btn-ghost btn-sm text-primary"
                    />
                </x-slot:subtitle>
            </x-avatar>

            <x-button
                link="{{route('user.profile.edit') }}"
                icon="lucide-cog"
                label="Profile Settings"
                class="btn-ghost"
            />
        </div>
    </x-card>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mb-4" wire:loading.class="opacity-60">
        <x-card class="order-2 xl:order-1 xl:col-span-8" aria-live="polite">
            <x-header title="Recent Notifications" icon="bell" subtitle="Notification center improvements are in progress." size="text-xl" />

            <div class="text-center py-10">
                <x-icon name="lucide-hourglass" class="w-12 h-12 mx-auto mb-3 text-base-content/40" />
                <h3 class="text-base font-medium mb-1">Coming Soon</h3>
                <p class="text-sm text-base-content/70">We are reworking notifications to provide a better experience.</p>
            </div>
        </x-card>

        <div class="order-1 xl:order-2 xl:col-span-4">
            <x-card>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-1 gap-3" aria-live="polite">
                    @foreach ($this->summaryMetrics as $metric)
                        <x-platform.dashboard-stat
                            :title="$metric['label']"
                            :value="number_format((int) $metric['value'])"
                            :icon="$metric['icon']"
                            accent-class="text-primary"
                        />
                    @endforeach
                </div>
            </x-card>
        </div>
    </div>
</x-platform.shell>
