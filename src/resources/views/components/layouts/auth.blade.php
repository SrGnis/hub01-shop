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

    {{-- Flash Messages --}}
    @if (session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info'))
        <div class="bg-base-200 px-6 py-3">
            <div class="max-w-screen-2xl mx-auto">
                <x-flash-messages />
            </div>
        </div>
    @endif

    {{-- Main content centered on screen --}}
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">

        {{-- Logo/Brand --}}
        <div class="my-6">
            <a href="{{ route('project-search', \App\Models\ProjectType::first()) }}" class="flex items-center gap-2">
                <div class="w-10 h-10">
                    <img src="{{ asset('images/logo.svg') }}" alt="" class="w-full h-full object-contain">
                </div>
                <div class="hidden md:block text-2xl font-bold text-primary">
                    {{ config('app.name') }}
                </div>
            </a>
        </div>

        {{-- Auth Card --}}
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-base-100 shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer p-5 bg-neutral text-neutral-content mt-auto">
        <div class="mx-auto">
            <x-footer-links />
        </div>
        <div class="mx-auto">
            &copy; {{config('app.name')}} {{ date('Y') }}
        </div>
    </div>

    {{-- Toast area --}}
    <x-toast />
</body>
</html>
