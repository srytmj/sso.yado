<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <title inertia>{{ config('app.name', 'SSO Yado') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('theme');
                var resolved = stored === 'dark' || (stored !== 'light' && stored !== 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
                    ? 'dark'
                    : (stored === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', resolved === 'dark' ? 'yado-dark' : 'yado');
            } catch (e) {}
        })();
    </script>
</head>
<body class="font-sans antialiased bg-base-100 text-base-content min-h-screen">
    @inertia
</body>
</html>
