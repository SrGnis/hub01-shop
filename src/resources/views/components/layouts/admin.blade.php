<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin - {{ config('app.name', 'HUB01 Shop') }}</title>

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
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center">
                                    <svg class="h-8 w-8 text-red-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 16V7.2C21 6.0799 21 5.51984 20.782 5.09202C20.5903 4.71569 20.2843 4.40973 19.908 4.21799C19.4802 4 18.9201 4 17.8 4H6.2C5.07989 4 4.51984 4 4.09202 4.21799C3.71569 4.40973 3.40973 4.71569 3.21799 5.09202C3 5.51984 3 6.0799 3 7.2V16M21 16C21 17.8856 21 18.8284 20.4142 19.4142C19.8284 20 18.8856 20 17 20H7C5.11438 20 4.17157 20 3.58579 19.4142C3 18.8284 3 17.8856 3 16M21 16H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 4V20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.5 9.5H11.5M12.5 14.5H20.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="ml-2 text-xl font-bold"><span class="text-red-500">ADMIN</span> Panel</span>
                                </a>
                            </div>
                        </div>

                        <!-- User Profile / Auth Links -->
                        <div class="flex items-center">
                            <a href="{{ route('project-search', \App\Models\ProjectType::first()) }}" class="text-gray-300 hover:text-white mr-4">
                                Back to Site
                            </a>
                            
                            <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-300 hover:text-white focus:outline-none transition duration-150 ease-in-out">
                                    <span>{{ Auth::user()->name }}</span>
                                    <svg class="ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-zinc-700 ring-1 ring-black ring-opacity-5" style="display: none;">
                                    <a href="{{ route('user.profile', Auth::user()) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-zinc-600 hover:text-white">Your Profile</a>
                                    <livewire:auth.logout />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Admin Navigation -->
            <div class="bg-zinc-800 border-b border-zinc-700 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
                    <div class="flex space-x-8 h-12">
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.dashboard') ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-300' }} text-sm font-medium">
                            @svg('lucide-home', 'w-5 h-5 mr-1')
                            Dashboard
                        </a>
                        <a href="{{ route('admin.users') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.users') ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-300' }} text-sm font-medium">
                            @svg('lucide-users', 'w-5 h-5 mr-1')
                            Users
                        </a>
                        <a href="{{ route('admin.projects') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.projects') ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-300' }} text-sm font-medium">
                            @svg('lucide-package', 'w-5 h-5 mr-1')
                            Projects
                        </a>
                        <a href="{{ route('admin.site') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.site') ? 'border-red-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-300' }} text-sm font-medium">
                            @svg('lucide-settings', 'w-5 h-5 mr-1')
                            Site Settings
                        </a>
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

            <!-- Status Messages -->
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
