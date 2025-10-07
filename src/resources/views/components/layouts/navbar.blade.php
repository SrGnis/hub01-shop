<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
 
    {{-- The navbar with `sticky` and `full-width` --}}
    <x-nav sticky full-width>
 
        <x-slot:brand>
            {{-- Drawer toggle for "main-drawer" --}}
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
 
            {{-- Brand --}}
            <div>App</div>
        </x-slot:brand>
 
        {{-- Right side actions --}}
        <x-slot:actions>
            @if($user = auth()->user())
                {{-- Authenticated User Actions --}}
                <x-button icon="o-envelope" link="###" class="btn-ghost btn-sm btn-circle" tooltip-left="Messages" />
                <x-button icon="o-bell" link="###" class="btn-ghost btn-sm btn-circle" tooltip-left="Notifications" />
                
                {{-- User Dropdown --}}
                <x-dropdown>
                    <x-slot:trigger>
                        <x-button class="btn-ghost btn-sm btn-circle" icon="o-user" />
                    </x-slot:trigger>
                    
                    <x-menu-item title="{{ $user->name }}" subtitle="{{ $user->email }}" no-hover />
                    <x-menu-separator />
                    
                    <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
                        @csrf
                    </form>
                    <x-menu-item
                        title="Logout"
                        icon="o-power"
                        @click.prevent="$refs.logoutForm.submit()"
                    />
                </x-dropdown>
            @else
                {{-- Guest Links --}}
                <x-button label="Login" icon="o-arrow-right-on-rectangle" link="{{ route('login') }}" class="btn-ghost btn-sm" responsive />
                @if (Route::has('register'))
                    <x-button label="Register" icon="o-user-plus" link="{{ route('register') }}" class="btn-primary btn-sm" responsive />
                @endif
            @endif
        </x-slot:actions>
    </x-nav>
 
    {{-- The main content with `full-width` --}}
    <x-main with-nav full-width>
        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>
 
    {{--  TOAST area --}}
    <x-toast />
</body>
</html>
