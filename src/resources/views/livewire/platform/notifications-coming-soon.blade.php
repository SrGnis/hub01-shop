<x-platform.shell>
    <x-slot:header>
        <x-header
            title="Notifications"
            icon="lucide-bell"
            icon-classes="w-6 h-6"
            subtitle="Notification center improvements are in progress."
            class="!mb-0"
        />
    </x-slot:header>

    <x-slot:left>
        <x-platform.section-nav current="notifications" />
    </x-slot:left>

    <x-card>
        <div class="text-center py-16">
            <x-icon name="lucide-hourglass" class="w-14 h-14 mx-auto mb-4 text-base-content/40" />
            <h3 class="text-lg font-medium mb-2">Coming Soon</h3>
            <p class="text-sm text-base-content/60">
                We are reworking notifications to provide a better experience.
            </p>
        </div>
    </x-card>
</x-platform.shell>

