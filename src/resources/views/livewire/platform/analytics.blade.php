<x-platform.shell>

    <x-slot:header>
        <x-header
            title="Analytics"
            subtitle="Track your global stats and daily download trends per project."
            class="!mb-0"
        />
    </x-slot:header>

    <x-slot:left>
        <x-platform.section-nav current="analytics" />
    </x-slot:left>

    <livewire:analytics.panel scope="workspace" />
</x-platform.shell>
