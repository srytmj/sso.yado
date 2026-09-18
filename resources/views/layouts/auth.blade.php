<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <title>@yield('title') - {{ __('common.app_name') }}</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center px-4 py-10">
    <div class="absolute top-4 right-4">
        @include('partials.theme-toggle')
    </div>
    <div class="w-full max-w-md animate-slide-up">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-base-content">yado</h1>
            <p class="text-sm text-base-content/60 mt-1">@yield('subtitle')</p>
        </div>
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body px-6 py-8 sm:px-8 sm:py-10">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
