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
<body class="antialiased bg-base-200 text-base-content">

    {{ $slot }}

    {{-- Toast area --}}
    <x-toast />
    {{-- Cookie Consent --}}
    <x-cookie-consent />
</body>
</html>
