@php
    $allProjectTypes = \App\Models\ProjectType::all();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    {{-- Cropper.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    {{-- ACE Editor --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.39.1/ace.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.39.1/ext-language_tools.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- Navbar --}}
    <div class="bg-base-100 border-base-content/10 border-b-[length:var(--border)] sticky top-0 z-10">
        <div class="flex items-center justify-between px-6 py-3 max-w-screen-2xl mx-auto">
            {{-- Brand (Left) --}}
            <div class="flex-shrink-0">
                <a href="{{ route('project-search', \App\Models\ProjectType::first()) }}" class="flex items-center gap-2">
                    <div class="w-8 h-8">
                        <img src="{{ asset('images/logo.svg') }}" alt="" class="w-full h-full object-contain">
                    </div>
                    <div class="hidden md:block text-xl font-bold text-primary">
                        {{ config('app.name') }}
                    </div>
                </a>
            </div>

            {{-- Navigation (Center) --}}
            <div class="absolute left-1/2 transform -translate-x-1/2">
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
            </div>

            {{-- Actions (Right) --}}
            <div class="flex-shrink-0 flex items-center gap-3">
                @if($user = auth()->user())
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="plus" class="btn-circle btn-ghost" />
                        </x-slot:trigger>
                        <x-menu class="p-0">
                            @foreach ($allProjectTypes as $projectType)
                                <x-menu-item title="New {{ $projectType->display_name }}" icon="{{ $projectType->icon }}" link="{{ route('project.create', $projectType) }}" />
                            @endforeach
                        </x-menu>
                    </x-dropdown>
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
                            <x-menu-item title="Profile" icon="user" link="{{ route('user.profile', $user) }}" />

                            @if ($user->isAdmin())
                                <x-menu-item title="Admin" icon="settings" link="{{ route('admin.dashboard') }}" />
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
    </div>

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
            <div class="max-w-screen-xl mx-auto">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-main>


    {{--  TOAST area --}}
    <x-toast />
    {{-- Footer --}}
    <div class="footer p-5 bg-neutral text-neutral-content mt-auto">
        <div class="mx-auto">
            <x-footer-links />
        </div>
        <div class="mx-auto">
            &copy; {{config('app.name')}} {{ date('Y') }}
        </div>
    </div>
</body>
</html>
