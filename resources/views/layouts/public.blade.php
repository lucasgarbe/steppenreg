<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', app(\App\Settings\EventSettings::class)->event_name)</title>

    <!-- Meta tags -->
    <meta name="description" content="@yield('description', __('public.registration.subtitle'))">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset(app(\App\Settings\EventSettings::class)->organization_logo_path) }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset(app(\App\Settings\EventSettings::class)->organization_logo_path) }}">

    <!-- Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])

    <!-- Dynamic Theme Colors -->
    <x-dynamic-theme-colors />

    <!-- Additional head content -->
    @stack('head')
</head>
<body class="bg-spw-offwhite min-h-screen font-sans @yield('body-class', 'py-8')">
    <!-- Header -->
    @if(!isset($hideHeader) || !$hideHeader)
        <x-public.header />
    @endif

    <!-- Main content -->
    <main class="@yield('main-class', 'max-w-2xl mx-auto px-4')">
        <div class="@yield('container-class', 'bg-white p-8 border-spw-black border-2 shadow-[2px_3px_0_rgb(0,0,0)] rounded-md')">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    @if(!isset($hideFooter) || !$hideFooter)
        <x-public.footer />
    @endif

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>
