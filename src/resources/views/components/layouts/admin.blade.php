<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' : '' }} {{ config('app.name') }} Admin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen font-sans antialiased bg-base-200/50">

    {{-- Admin Navigation --}}
    <x-nav sticky full-width class="bg-base-100 shadow-md">
        <x-slot:brand>
            <a href="{{ route('admin.dashboard') }}" class="font-semibold text-xl">
                <x-icon name="lucide-shield-check" class="w-6 h-6 inline" /> Admin Panel
            </a>
        </x-slot:brand>

        <x-slot:actions>
            <x-button label="View Site" link="/" icon="lucide-external-link" class="btn-ghost btn-sm" />
            <x-dropdown>
                <x-slot:trigger>
                    <x-button icon="lucide-user" class="btn-circle btn-ghost">
                        <x-avatar :title="Auth::user()->name" class="!w-8" />
                    </x-button>
                </x-slot:trigger>

                <x-menu-item title="Profile" icon="lucide-user" link="{{ route('user.profile.edit') }}" />
                <x-menu-separator />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-menu-item title="Logout" icon="lucide-log-out"
                        onclick="event.preventDefault(); this.closest('form').submit();" />
                </form>
            </x-dropdown>
        </x-slot:actions>
    </x-nav>

    {{-- SIDEBAR --}}
    <x-main with-nav full-width>
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 border-r border-base-300">
            <x-menu activate-by-route>
                <x-menu-item title="Dashboard" icon="lucide-layout-dashboard" link="{{ route('admin.dashboard') }}" />
                <x-menu-separator />

                <x-menu-title title="Management" />
                <x-menu-item title="Users" icon="lucide-users" link="{{ route('admin.users') }}" />
                <x-menu-item title="Projects" icon="lucide-package" link="{{ route('admin.projects') }}" />

                <x-menu-separator />

                <x-menu-title title="Configuration" />
                <x-menu-item title="Site Settings" icon="lucide-settings" link="{{ route('admin.site') }}" />
            </x-menu>
        </x-slot:sidebar>

        {{-- CONTENT --}}
        <x-slot:content>
            <x-flash-messages />
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{-- Toast --}}
    <x-toast />
</body>

</html>
