<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HUB01 Shop') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @bukStyles
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-zinc-900 text-white">
            <!-- Top Navigation Bar -->
            <nav class="bg-zinc-800 border-b border-zinc-700">
                <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <!-- Logo and App Name -->
                        <div class="flex">
                            <div class="flex-shrink-0 flex items-center">
                                <a href="{{ route('project-search', \App\Models\ProjectType::where('value', 'mod')->first()) }}" class="flex items-center">
                                    <img src="{{ asset('images/logo.svg') }}" alt="" class="h-12 w-12">
                                    <span class="ml-2 text-xl font-bold">{{ config('app.name') }}</span>
                                </a>
                            </div>
                        </div>

                        <!-- User Profile / Auth Links -->
                        <div class="flex items-center">
                            @auth
                                <!-- Create Project Dropdown -->
                                <div class="relative mr-4" x-data="{ open: false }" @click.away="open = false">
                                    <button @click="open = !open" class="inline-flex items-center justify-center  text-white rounded-full w-8 h-8" title="Create New Project">
                                        @svg('lucide-plus', 'w-5 h-5')
                                    </button>
                                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-zinc-700 ring-1 ring-black ring-opacity-5" style="display: none;">
                                        @foreach (\App\Models\ProjectType::all() as $projectType)
                                            <a href="{{ route('project.create', $projectType) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-zinc-600 hover:text-white">
                                                New {{ $projectType->display_name }}
                                                @svg($projectType->icon, 'w-5 h-5 inline-block ml-2')
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                    <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-300 hover:text-white focus:outline-none transition duration-150 ease-in-out">
                                        <span>{{ Auth::user()->name }}</span>
                                        @svg('lucide-chevron-down', 'w-5 h-5 ml-1')
                                    </button>

                                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-zinc-700 ring-1 ring-black ring-opacity-5" style="display: none;">
                                        <a href="{{ route('user.profile', Auth::user()) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-zinc-600 hover:text-white">Your Profile</a>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-zinc-600 hover:text-white">Settings</a>
                                        @if(Auth::user()->isAdmin())
                                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-zinc-600 hover:text-white">Admin Panel</a>
                                        @endif
                                        <livewire:auth.logout />
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Log in</a>
                                <a href="{{ route('register') }}" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Sign up</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Section Navigation -->
            <div class="bg-zinc-800 border-b border-zinc-700 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
                    <div class="flex space-x-8 h-12">
                        @foreach (\App\Models\ProjectType::all() as $projectType)
                            @php
                                $routeProjectType = request()->route('projectType');
                                $isActive = is_string($routeProjectType)
                                    ? $routeProjectType === $projectType->value
                                    : $routeProjectType?->value === $projectType->value;
                            @endphp
                            <a href="{{ route('project-search', $projectType) }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ $isActive ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-300' }} text-sm font-medium">
                                @svg($projectType->icon, 'w-5 h-5 mr-1')
                                {{ $projectType->pluralizedDisplayName() }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-zinc-800 shadow">
                    <div class="max-w-7xl mx-auto py-4 px-4 lg:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Email Verification Status -->
            @if (session('message'))
                <div class="bg-green-600 text-white p-4">
                    <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8 flex justify-between items-center">
                        <p>{{ session('message') }}</p>
                        <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                            @svg('lucide-x', 'w-5 h-5')
                        </button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-400 text-white p-4">
                    <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8 flex justify-between items-center">
                        <p>{{ session('error') }}</p>
                        <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                            @svg('lucide-x', 'w-5 h-5')
                        </button>
                    </div>
                </div>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Blade Icons -->
        <svg hidden class="hidden">
            @stack('bladeicons')
        </svg>
        @bukScripts
    </body>
</html>


