@php
    $allProjectTypes = all_project_types();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    {{-- Cropper.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    {{-- ACE Editor --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.39.1/ace.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.39.1/ext-language_tools.min.js"></script>

    {{-- Chart.js  --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-head-elements />
</head>
<body class="min-h-screen flex flex-col font-sans antialiased bg-base-200">

    {{-- Skip to content --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-primary focus:text-primary-content focus:px-4 focus:py-2 focus:rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
        Skip to content
    </a>

    {{-- Navbar --}}
    <header class="bg-base-100 border-base-content/10 border-b-[length:var(--border)] sticky top-0 z-10">
        <div class="flex items-center justify-between px-6 py-3 max-w-screen-2xl mx-auto">
            {{-- Brand (Left) --}}
            <div class="flex-shrink-0">
                <a href="{{ route('project-search', \App\Models\ProjectType::first()) }}" class="flex items-center gap-2">
                    <div class="w-8 h-8">
                        <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }} home" class="w-full h-full object-contain">
                    </div>
                    <div class="hidden md:block text-xl font-bold">
                        <span>{{ config('app.name') }}</span>
                    </div>
                </a>
            </div>

            {{-- Navigation (Center) --}}
            <nav aria-label="Discover" class="absolute left-1/2 transform -translate-x-1/2">
                {{-- Desktop: Horizontal buttons --}}
                <div class="hidden md:flex items-center gap-1">
                    @foreach ($allProjectTypes as $projectType)
                        <a href="{{ route('project-search', $projectType) }}" class="btn btn-ghost btn-sm">
                            <x-icon :name="$projectType->icon" class="w-5 h-5" />
                            <span>{{ $projectType->pluralizedDisplayName() }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- Mobile: Dropdown --}}
                <div class="md:hidden">
                    <x-dropdown>

                        <x-slot:trigger>
                            <x-button label="Discover" icon="search" class="btn-ghost btn-sm" />
                        </x-slot:trigger>

                        @foreach ($allProjectTypes as $projectType)
                            <x-menu-item
                                title="{{ $projectType->pluralizedDisplayName() }}"
                                icon="{{ $projectType->icon }}"
                                link="{{ route('project-search', $projectType) }}"
                            />
                        @endforeach
                    </x-dropdown>
                </div>
            </nav>

            {{-- Actions (Right) --}}
             <div class="flex-shrink-0 flex items-center gap-3">
                 @if($user = auth()->user())
                     <x-button
                         icon="plus"
                         class="btn-ghost hidden md:flex"
                         onclick="Livewire.dispatch('open-project-create-modal')"
                         aria-label="Create new project"
                         label="Publish"
                     />
                    {{-- User Dropdown --}}
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-avatar
                                placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}"
                                placeholder-text-class="font-bold"
                                placeholder-bg-class="bg-primary text-primary-content"
                                class="cursor-pointer w-10"
                                image="{{ $user->getAvatarUrl() }}"
                            >
                            </x-avatar>
                        </x-slot:trigger>
                        <x-menu class="p-0">
                            <x-menu-item title="Publish" icon="plus" class="md:hidden bg-primary text-primary-content" onclick="Livewire.dispatch('open-project-create-modal')" no-wire-navigate/>
                            <x-menu-item title="Dashboard" icon="lucide-layout-dashboard" link="{{ route('platform.dashboard') }}" no-wire-navigate/>
                            <x-menu-item title="Profile" icon="user" link="{{ route('user.profile', $user) }}" no-wire-navigate/>
                            <x-menu-item title="Projects" icon="package" link="{{ route('platform.projects') }}" no-wire-navigate/>
                            <x-menu-item title="Collections" icon="lucide-folder-open" link="{{ route('platform.collections') }}" no-wire-navigate/>

                            @if ($user->isAdmin())
                                <x-menu-item title="Admin" icon="settings" link="{{ route('admin.dashboard') }}" no-wire-navigate/>
                            @endif

                            <x-menu-separator />

                            <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
                                @csrf
                            </form>
                            <x-menu-item
                                title="Logout"
                                icon="log-out"
                                icon-classes="text-error"
                                @click.prevent="$refs.logoutForm.submit()"
                                no-wire-navigate
                            />
                        </x-menu>
                    </x-dropdown>
                @else
                    {{-- Login & Register Dropdown --}}
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button label="Account" icon="user-circle" class="btn-ghost btn-sm" responsive />
                        </x-slot:trigger>

                        <x-menu-item
                            title="Login"
                            icon="log-in"
                            link="{{ route('login') }}"
                        />
                        <x-menu-item
                            title="Register"
                            icon="user-plus"
                            link="{{ route('register') }}"
                        />
                    </x-dropdown>
                @endif
            </div>
        </div>
    </header>

    {{-- Flash Messages --}}
    @if (session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info'))
        <div class="bg-base-200 px-6 py-3">
            <div class="max-w-screen-2xl mx-auto">
                <x-flash-messages />
            </div>
        </div>
    @endif

    {{-- The main content with `full-width` --}}
    <x-main with-nav full-width>
        {{-- The `$slot` goes here --}}
        <x-slot:content>
            <main id="main-content" class="max-w-screen-xl mx-auto">
                {{ $slot }}
            </main>
        </x-slot:content>
    </x-main>

    {{-- Footer --}}
    <footer class="border-t border-base-content/10 mt-auto">
        <div class="flex flex-col md:flex-row items-center justify-between max-w-[960px] mx-auto px-8 py-5 gap-4">
            <span class="text-xs text-base-content/50">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <div class="flex gap-4">
                <x-footer-links />
            </div>
        </div>
    </footer>

    {{--  TOAST area --}}
    <x-toast />

    {{-- Cookie Consent --}}
    <x-cookie-consent />

    {{-- Project Create Modal --}}
    @livewire('project-create-modal')

</body>
</html>
