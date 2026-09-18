<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <title>@yield('title', __('account.title')) - {{ __('common.app_name') }}</title>
    @include('partials.theme-init', ['theme' => auth()->user()?->theme])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200">
    {{-- Mobile tab nav --}}
    <div class="md:hidden bg-base-100 border-b border-base-300">
        <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
            <a href="/" class="font-semibold text-base-content text-sm">yado</a>
            <div class="flex items-center gap-2">
                @include('partials.theme-toggle')
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm text-base-content/60 hover:text-base-content">{{ __('common.sign_out') }}</button>
                </form>
            </div>
        </div>
        <div role="tablist" class="tabs tabs-lift px-4">
            <a role="tab" href="{{ route('account.show') }}"
               class="tab {{ request()->routeIs('account.show') ? 'tab-active font-medium' : '' }}">
                {{ __('account.nav_account') }}
            </a>
            <a role="tab" href="{{ route('account.sessions') }}"
               class="tab {{ request()->routeIs('account.sessions') ? 'tab-active font-medium' : '' }}">
                {{ __('account.nav_sessions') }}
            </a>
            <a role="tab" href="{{ route('account.two-factor.show') }}"
               class="tab {{ request()->routeIs('account.two-factor.*') ? 'tab-active font-medium' : '' }}">
                {{ __('two_factor.title') }}
            </a>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex gap-10">
            {{-- Sidebar desktop --}}
            <aside class="hidden md:block w-44 shrink-0">
                <div class="flex items-center justify-between mb-5">
                    <a href="/" class="block font-semibold text-sm text-base-content">yado</a>
                    @include('partials.theme-toggle')
                </div>
                <nav class="mb-6">
                    <ul class="menu menu-sm gap-1 p-0 w-full">
                        <li>
                            <a href="{{ route('account.show') }}"
                               class="rounded-lg {{ request()->routeIs('account.show') ? 'menu-active bg-primary/10 text-primary font-medium' : 'text-base-content/60 hover:text-base-content' }}">
                                {{ __('account.nav_account') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('account.sessions') }}"
                               class="rounded-lg {{ request()->routeIs('account.sessions') ? 'menu-active bg-primary/10 text-primary font-medium' : 'text-base-content/60 hover:text-base-content' }}">
                                {{ __('account.nav_sessions') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('account.two-factor.show') }}"
                               class="rounded-lg {{ request()->routeIs('account.two-factor.*') ? 'menu-active bg-primary/10 text-primary font-medium' : 'text-base-content/60 hover:text-base-content' }}">
                                {{ __('two_factor.title') }}
                            </a>
                        </li>
                    </ul>
                </nav>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-xs text-base-content/40 hover:text-base-content/70 px-3">{{ __('common.sign_out') }}</button>
                </form>
            </aside>

            <main class="flex-1 min-w-0">
                @if(session('success'))
                    <div class="alert alert-success alert-soft mb-6 text-sm animate-slide-up" role="alert">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error alert-soft mb-6 text-sm animate-slide-up" role="alert">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
