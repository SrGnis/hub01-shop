<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' : '' }} {{ config('app.name') }} Admin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <div class="ml-5 pt-5">
                <a href="{{ route('admin.dashboard') }}" class="font-semibold text-xl">
                    <x-icon name="lucide-shield-check" class="w-6 h-6 inline" /> Admin Panel
                </a>
            </div>
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main full-width>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100">

            {{-- BRAND --}}
            <div class="ml-5 pt-5">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8">
                        <img src="{{ asset('images/logo.svg') }}" alt="" class="w-full h-full object-contain">
                    </div>
                    <div class="hidden md:block text-xl font-bold text-primary hidden-when-collapsed">
                        {{ config('app.name') }}
                    </div>
                </a>
            </div>

            {{-- MENU --}}
            <x-menu activate-by-route>

                {{-- User --}}
                @if($user = auth()->user())
                    <x-menu-separator />
                    <x-dropdown>
                        <x-slot:trigger>
                            <a>
                                <x-list-item :item="$user" no-separator class="-mx-2 !-my-2 rounded">
                                    <x-slot:avatar>
                                        <x-avatar
                                            placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}"
                                            placeholder-text-class="font-bold"
                                            placeholder-bg-class="bg-primary text-primary-content"
                                            class="cursor-pointer w-10"
                                            image="{{ $user->getAvatarUrl() }}"
                                        >
                                        </x-avatar>
                                    </x-slot:avatar>
                                    <x-slot:value>
                                        {{ $user->name }}
                                    </x-slot:value>
                                    <x-slot:sub-value>
                                        {{ $user->email }}
                                    </x-slot:sub-value>
                                    <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
                                        @csrf
                                    </form>
                                </x-list-item>
                            </a>
                        </x-slot:trigger>
                        <x-menu class="p-0 !w-60">
                            <x-menu-item title="Profile" icon="user" link="{{ route('user.profile', $user) }}" />

                            <x-menu-separator />

                            <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
                                @csrf
                            </form>
                            <x-menu-item
                                title="Logout"
                                icon="log-out"
                                icon-classes="text-error"
                                @click.prevent="$refs.logoutForm.submit()"
                            />
                        </x-menu>
                    </x-dropdown>

                    <x-menu-separator />
                @endif

                <x-menu-item title="Dashboard" icon="lucide-layout-dashboard" link="{{ route('admin.dashboard') }}" />
                <x-menu-separator />

                <x-menu-title class="hidden-when-collapsed" title="Management" />
                <x-menu-item title="Users" icon="lucide-users" link="{{ route('admin.users') }}" />
                <x-menu-item title="Projects" icon="lucide-package" link="{{ route('admin.projects') }}" />

                <x-menu-separator />

                <x-menu-title class="hidden-when-collapsed" title="Configuration" />
                <x-menu-item title="Site Settings" icon="lucide-settings" link="{{ route('admin.site') }}" />
            </x-menu>
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            <x-flash-messages />
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{-- Toast --}}
    <x-toast />
</body>
</html>
