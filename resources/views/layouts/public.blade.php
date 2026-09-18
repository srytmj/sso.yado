<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('common.app_name') . ' — yado')</title>
    @include('partials.theme-init', ['theme' => auth()->user()?->theme])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-100 text-base-content flex flex-col relative overflow-x-hidden">
    {{-- Blob gradient dekoratif di background — dasar buat efek kaca (backdrop-blur)
         di card/badge atasnya kelihatan, bukan cuma transparan ke warna polos. --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-32 -left-24 w-96 h-96 bg-primary/30 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 -right-24 w-96 h-96 bg-secondary/25 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-accent/20 rounded-full blur-3xl"></div>
    </div>

    <div class="fixed top-4 right-4 z-10 bg-base-100/60 backdrop-blur-md border border-base-300/40 rounded-full shadow-sm">
        @include('partials.theme-toggle')
    </div>
    <main class="flex-1">
        @yield('content')
    </main>
    <footer class="border-t border-base-200 py-8 mt-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 text-center">
            <p class="text-sm text-base-content/40">© {{ date('Y') }} yado · {{ __('common.app_name') }}</p>
        </div>
    </footer>
</body>
</html>
