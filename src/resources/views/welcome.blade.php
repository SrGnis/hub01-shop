<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ config('app.name') }} - Your ultimate destination for game mods and extensions. Discover, download, and share mods for your favorite games.">
    <title>{{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/welcome.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-zinc-900 text-white">
    <div class="relative min-h-screen">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-grid-pattern opacity-5 z-0"></div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4 lg:px-6 lg:px-8">
            <!-- Logo and Header -->
            <div class="text-center mb-12 animate-fade-in">
                <div class="flex justify-center mb-6">
                    <div class="w-32 h-32">
                        <img src="{{ asset('images/logo.svg') }}" alt="" class="w-full h-full object-contain">
                    </div>
                </div>
                <h1 class="text-5xl font-bold tracking-tight text-white lg:text-6xl mb-4">
                    {{ config('app.name') }}
                </h1>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    Your ultimate destination for Cataclylg: Dark Days Ahead mods and extensions.
                </p>
            </div>

            <!-- Features -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12 max-w-5xl w-full animate-fade-in-delay-1">
                <div class="bg-zinc-800 p-6 rounded-lg border border-zinc-700 hover:border-indigo-500 transition-colors feature-card">
                    <div class="flex items-center mb-4">
                        @svg('lucide-search', 'w-6 h-6 text-indigo-500 mr-2')
                        <h3 class="text-lg font-semibold">Discover</h3>
                    </div>
                    <p class="text-gray-400">
                        Browse through a collection of mods across multiple categories.
                    </p>
                </div>

                <div class="bg-zinc-800 p-6 rounded-lg border border-zinc-700 hover:border-indigo-500 transition-colors feature-card">
                    <div class="flex items-center mb-4">
                        @svg('lucide-download', 'w-6 h-6 text-indigo-500 mr-2')
                        <h3 class="text-lg font-semibold">Download</h3>
                    </div>
                    <p class="text-gray-400">
                        Get easy access to the latest versions with dependency management.
                    </p>
                </div>

                <div class="bg-zinc-800 p-6 rounded-lg border border-zinc-700 hover:border-indigo-500 transition-colors feature-card">
                    <div class="flex items-center mb-4">
                        @svg('lucide-share-2', 'w-6 h-6 text-indigo-500 mr-2')
                        <h3 class="text-lg font-semibold">Share</h3>
                    </div>
                    <p class="text-gray-400">
                        Upload your own creations and share them with the community.
                    </p>
                </div>
            </div>

            <!-- CTA Button and Footer -->
            <div class="text-center animate-fade-in-delay-2 relative">
                <a href="{{ route('project-search', \App\Models\ProjectType::first()) }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors pulse-animation">
                    @svg('lucide-package', 'w-5 h-5 mr-2')
                    Browse Mods
                </a>
            </div>

            <!-- Footer for all screen sizes -->
            <div class="w-full py-6 text-center text-gray-500 animate-fade-in-delay-3">
                <div class="mb-4 flex justify-center space-x-4">
                    <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300 transition-colors">Login</a>
                    <a href="{{ route('register') }}" class="text-indigo-400 hover:text-indigo-300 transition-colors">Register</a>
                </div>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>

    <style>
        .bg-grid-pattern {
            background-image:
                linear-gradient(to right, rgba(75, 85, 99, 0.1) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(75, 85, 99, 0.1) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        @media (max-height: 600px) {
            .md\:hidden {
                display: block !important;
            }
            .md\:block {
                display: none !important;
            }
        }
    </style>
</body>
</html>
