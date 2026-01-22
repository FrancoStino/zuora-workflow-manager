<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? config('app.name', 'Zuora Workflow Manager') }}</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">

    {{-- Vite Assets (Tailwind CSS v4) --}}
    @vite(['resources/css/app.css'])

    {{-- Dark mode detection --}}
    <script>
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>

    {{ $head ?? '' }}
</head>
<body class="h-full bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
<div class="flex min-h-full flex-col items-center justify-center px-6 py-12 gap-12">
    {{-- Logo --}}
    <div>
        <img
            src="{{ asset('images/logo.svg') }}"
            alt="{{ config('app.name', 'Zuora Workflow Manager') }}"
            class="h-16 w-auto dark:hidden"
        >
        <img
            src="{{ asset('images/logo-white.svg') }}"
            alt="{{ config('app.name', 'Zuora Workflow Manager') }}"
            class="h-16 w-auto hidden dark:block"
        >
    </div>

    {{-- Content --}}
    {{ $slot }}
</div>


{{ $scripts ?? '' }}
</body>
</html>
