@extends('layouts.public')

@section('title', __('common.app_name') . ' - yado')

@section('content')
    <div class="max-w-5xl mx-auto px-6 py-16 sm:py-24">

        {{-- Hero --}}
        <div class="text-center mb-16 sm:mb-24 animate-slide-up">
            <span class="badge badge-primary badge-soft backdrop-blur-sm mb-6">{{ __('home.badge') }}</span>
            <h2 class="text-4xl font-bold text-base-content mb-4 leading-tight">{{ __('common.app_name') }}</h2>
            <p class="text-lg text-base-content/60 max-w-xl mx-auto mb-8 leading-relaxed">
                {{ __('home.tagline') }}<br class="hidden sm:block">
                {{ __('home.tagline_second_line') }}
            </p>

            @if($user ?? null)
                <div class="flex items-center justify-center gap-3">
                    @if($user->role?->slug === 'superadmin')
                        <a href="{{ route('dashboard.index') }}" class="btn btn-neutral active:scale-95">{{ __('home.open_dashboard') }}</a>
                    @else
                        <a href="{{ route('account.show') }}" class="btn btn-neutral active:scale-95">{{ __('home.view_my_account') }}</a>
                    @endif
                </div>
            @else
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('login') }}" class="btn btn-neutral w-full sm:w-auto active:scale-95">{{ __('common.sign_in') }}</a>
                    <a href="{{ route('register') }}" class="btn btn-ghost w-full sm:w-auto active:scale-95">{{ __('home.register_now') }}</a>
                </div>
            @endif
        </div>

        {{-- Features --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <div class="card bg-base-100/60 backdrop-blur-md border border-base-300/40 shadow-sm animate-fade-in">
                <div class="card-body p-6">
                <div class="w-9 h-9 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                </div>
                <h3 class="text-sm font-semibold text-base-content mb-2">{{ __('home.feature_sso_title') }}</h3>
                <p class="text-sm text-base-content/60 leading-relaxed">{{ __('home.feature_sso_description') }}</p>
                </div>
            </div>

            <div class="card bg-base-100/60 backdrop-blur-md border border-base-300/40 shadow-sm animate-fade-in">
                <div class="card-body p-6">
                <div class="w-9 h-9 bg-secondary/10 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                </div>
                <h3 class="text-sm font-semibold text-base-content mb-2">{{ __('home.feature_oauth_title') }}</h3>
                <p class="text-sm text-base-content/60 leading-relaxed">{{ __('home.feature_oauth_description') }}</p>
                </div>
            </div>

            <div class="card bg-base-100/60 backdrop-blur-md border border-base-300/40 shadow-sm animate-fade-in">
                <div class="card-body p-6">
                <div class="w-9 h-9 bg-accent/10 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                </div>
                <h3 class="text-sm font-semibold text-base-content mb-2">{{ __('home.feature_register_title') }}</h3>
                <p class="text-sm text-base-content/60 leading-relaxed">{{ __('home.feature_register_description') }}</p>
                </div>
            </div>

            <div class="card bg-base-100/60 backdrop-blur-md border border-base-300/40 shadow-sm animate-fade-in">
                <div class="card-body p-6">
                <div class="w-9 h-9 bg-warning/10 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                </div>
                <h3 class="text-sm font-semibold text-base-content mb-2">{{ __('home.feature_token_title') }}</h3>
                <p class="text-sm text-base-content/60 leading-relaxed">{{ __('home.feature_token_description') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
