<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- Main content centered on screen --}}
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        
        {{-- Logo/Brand --}}
        <div class="mb-6">
            <a href="/" class="text-2xl font-bold text-primary">
                {{ config('app.name') }}
            </a>
        </div>

        {{-- Auth Card --}}
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-base-100 shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>

        {{-- Footer Links --}}
        <div class="mt-6 text-center">
            <div class="flex justify-center space-x-4 text-sm text-base-content/70">
                <a href="/" class="hover:text-primary transition-colors">
                    Home
                </a>
                <span>•</span>
                <a href="#" class="hover:text-primary transition-colors">
                    Help
                </a>
                <span>•</span>
                <a href="#" class="hover:text-primary transition-colors">
                    Privacy
                </a>
            </div>
        </div>
    </div>

    {{-- Toast area --}}
    <x-toast />
</body>
</html>
