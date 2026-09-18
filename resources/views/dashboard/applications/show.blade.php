@extends('layouts.dashboard')
@section('title', $client->name)
@section('heading', $client->name)

@section('content')
    @if(session('new_secret'))
        {{-- Quick Start Panel - only shown once after creation --}}
        <div class="mb-6 animate-slide-up" x-data="{
            clientId: '{{ $client->id }}',
            secret: '{{ session('new_secret') }}',
            redirectUri: '{{ $client->redirect }}',
            baseUrl: '{{ config('app.url') }}',
            copied: null,
            async copy(key, text) {
                await navigator.clipboard.writeText(text);
                this.copied = key;
                setTimeout(() => this.copied = null, 2000);
            },
            get envSnippet() {
                return `SSO_CLIENT_ID=${this.clientId}\nSSO_CLIENT_SECRET=${this.secret}\nSSO_BASE_URL={{ rtrim(config('app.url'), '/') }}\nSSO_REDIRECT_URI=${this.redirectUri}`;
            },
            get authUrl() {
                return `{{ rtrim(config('app.url'), '/') }}/oauth/authorize?client_id=${this.clientId}&redirect_uri=${encodeURIComponent(this.redirectUri)}&response_type=code&scope=profile:read&code_challenge=PKCE_CHALLENGE_HERE&code_challenge_method=S256`;
            }
        }">
            <div class="alert alert-warning alert-soft flex-col items-stretch p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="badge badge-warning">{{ __('applications.save_now_warning') }}</span>
                </div>

                {{-- Client ID --}}
                <div class="mb-3">
                    <p class="text-xs font-medium text-warning mb-1">{{ __('applications.client_id_label') }}</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 font-mono text-sm text-base-content bg-base-100 border border-warning/30 rounded-lg px-3 py-2 break-all">{{ $client->id }}</code>
                        <button @click="copy('id', clientId)" class="btn btn-outline btn-warning btn-sm active:scale-95">
                            <span x-show="copied !== 'id'">{{ __('applications.copy') }}</span>
                            <span x-show="copied === 'id'" class="text-success">{{ __('applications.copied') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Client Secret --}}
                <div class="mb-3">
                    <p class="text-xs font-medium text-warning mb-1">{{ __('applications.client_secret_label') }}</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 font-mono text-sm text-base-content bg-base-100 border border-warning/30 rounded-lg px-3 py-2 break-all">{{ session('new_secret') }}</code>
                        <button @click="copy('secret', secret)" class="btn btn-outline btn-warning btn-sm active:scale-95">
                            <span x-show="copied !== 'secret'">{{ __('applications.copy') }}</span>
                            <span x-show="copied === 'secret'" class="text-success">{{ __('applications.copied') }}</span>
                        </button>
                    </div>
                </div>

                {{-- .env snippet --}}
                <div class="mb-3">
                    <p class="text-xs font-medium text-warning mb-1">{{ __('applications.env_snippet_label') }}</p>
                    <div class="flex items-start gap-2">
                        <pre class="flex-1 font-mono text-xs text-base-content bg-base-100 border border-warning/30 rounded-lg px-3 py-2 overflow-x-auto whitespace-pre">SSO_CLIENT_ID={{ $client->id }}
SSO_CLIENT_SECRET={{ session('new_secret') }}
SSO_BASE_URL={{ rtrim(config('app.url'), '/') }}
SSO_REDIRECT_URI={{ $client->redirect }}</pre>
                        <button @click="copy('env', envSnippet)" class="btn btn-outline btn-warning btn-sm active:scale-95">
                            <span x-show="copied !== 'env'">{{ __('applications.copy') }}</span>
                            <span x-show="copied === 'env'" class="text-success">{{ __('applications.copied') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Authorization URL --}}
                <div>
                    <p class="text-xs font-medium text-warning mb-1">{{ __('applications.auth_url_label') }}</p>
                    <div class="flex items-start gap-2">
                        <code x-text="authUrl" class="flex-1 font-mono text-xs text-base-content bg-base-100 border border-warning/30 rounded-lg px-3 py-2 break-all block"></code>
                        <button @click="copy('url', authUrl)" class="btn btn-outline btn-warning btn-sm active:scale-95">
                            <span x-show="copied !== 'url'">{{ __('applications.copy') }}</span>
                            <span x-show="copied === 'url'" class="text-success">{{ __('applications.copied') }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-warning/80 mt-1.5">{!! __('applications.auth_url_hint', ['placeholder' => '<code class="bg-warning/20 px-1 rounded">PKCE_CHALLENGE_HERE</code>']) !!}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-lg space-y-6">
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body p-6">
                <h2 class="text-sm font-semibold text-base-content mb-4">{{ __('applications.credentials_heading') }}</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-base-content/60 mb-1">{{ __('applications.client_id_label') }}</p>
                        <code class="font-mono text-sm text-base-content">{{ $client->id }}</code>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/60 mb-1">{{ __('applications.client_secret_label') }}</p>
                        <p class="text-sm text-base-content/60 italic">{{ __('applications.secret_hidden') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/60 mb-1">{{ __('applications.redirect_uri_label') }}</p>
                        <code class="font-mono text-sm text-base-content break-all">{{ $client->redirect }}</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body p-6">
                <h2 class="text-sm font-semibold text-base-content mb-4">{{ __('applications.edit_heading') }}</h2>
                <form method="POST" action="{{ route('dashboard.applications.update', $client->id) }}" novalidate>
                    @csrf @method('PATCH')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('applications.name_label') }}</label>
                            <input type="text" name="name" value="{{ old('name', $client->name) }}"
                                class="input w-full" />
                            @error('name')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('applications.redirect_uri_label') }}</label>
                            <input type="url" name="redirect_uri" value="{{ old('redirect_uri', $client->redirect) }}"
                                class="input w-full" />
                            @error('redirect_uri')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-neutral active:scale-95">{{ __('applications.save_changes_button') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
