@extends('layouts.dashboard')
@section('title', __('sessions.title'))
@section('heading', __('sessions.title'))

@section('content')
    @if(session('error'))
        <div class="alert alert-error alert-soft mb-6 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- OAuth Sessions --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-3">
            <h2 class="text-sm font-semibold text-base-content">{{ __('sessions.oauth_heading') }}</h2>
            <span class="badge badge-neutral badge-soft">{{ $oauthSessions->count() }}</span>
        </div>
        <p class="text-sm text-base-content/60 mb-4">{{ __('sessions.oauth_description') }}</p>

        @if($oauthSessions->isEmpty())
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body px-6 py-10 text-center">
                    <p class="text-sm text-base-content/60">{{ __('sessions.oauth_empty') }}</p>
                </div>
            </div>
        @else
            <div class="card bg-base-100 border border-base-300">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>{{ __('sessions.th_user') }}</th>
                                <th>{{ __('sessions.th_application') }}</th>
                                <th class="hidden md:table-cell">{{ __('sessions.th_login_since') }}</th>
                                <th class="hidden lg:table-cell">{{ __('sessions.th_expires') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($oauthSessions as $session)
                                <tr>
                                    <td>
                                        <p class="font-medium text-base-content">{{ $session->user_name }}</p>
                                        <p class="text-xs text-base-content/60">{{ $session->user_email }}</p>
                                    </td>
                                    <td>
                                        <span class="badge badge-info badge-soft">
                                            {{ $session->client_name }}
                                        </span>
                                    </td>
                                    <td class="text-base-content/60 hidden md:table-cell">
                                        {{ \Carbon\Carbon::parse($session->created_at)->diffForHumans() }}
                                    </td>
                                    <td class="hidden lg:table-cell">
                                        @php $expiresAt = \Carbon\Carbon::parse($session->expires_at); @endphp
                                        @if($expiresAt->diffInMinutes(now()) < 10 && $expiresAt->isFuture())
                                            <span class="badge badge-warning badge-soft">{{ __('sessions.expired_soon') }}</span>
                                        @else
                                            <span class="text-xs text-base-content/60">{{ $expiresAt->diffForHumans() }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form method="POST"
                                            action="{{ route('dashboard.sessions.destroy', $session->id) }}?type=oauth"
                                            x-data
                                            @submit.prevent="if(confirm('{{ __('sessions.revoke_oauth_confirm', ['user' => $session->user_name, 'app' => $session->client_name]) }}')) $el.submit()">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-ghost btn-xs text-error active:scale-95">{{ __('sessions.revoke') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- SSO Web Sessions --}}
    <div>
        <div class="flex items-center gap-3 mb-3">
            <h2 class="text-sm font-semibold text-base-content">{{ __('sessions.web_heading') }}</h2>
            <span class="badge badge-neutral badge-soft">{{ $webSessions->count() }}</span>
        </div>
        <p class="text-sm text-base-content/60 mb-4">{{ __('sessions.web_description') }}</p>

        @if($webSessions->isEmpty())
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body px-6 py-10 text-center">
                    <p class="text-sm text-base-content/60">{{ __('sessions.web_empty') }}</p>
                </div>
            </div>
        @else
            <div class="card bg-base-100 border border-base-300">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>{{ __('sessions.th_user') }}</th>
                                <th class="hidden md:table-cell">{{ __('sessions.th_ip') }}</th>
                                <th class="hidden lg:table-cell">{{ __('sessions.th_last_active') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($webSessions as $session)
                                @php $isSelf = $session->id === session()->getId(); @endphp
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div>
                                                <p class="font-medium text-base-content">{{ $session->user_name }}</p>
                                                <p class="text-xs text-base-content/60">{{ $session->user_email }}</p>
                                            </div>
                                            @if($isSelf)
                                                <span class="badge badge-neutral badge-soft">{{ __('sessions.you') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-base-content/60 hidden md:table-cell">
                                        {{ $session->ip_address ?? '-' }}
                                    </td>
                                    <td class="text-base-content/60 hidden lg:table-cell">
                                        {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                                    </td>
                                    <td class="text-right">
                                        @if($isSelf)
                                            <span class="text-xs text-base-content/40 cursor-not-allowed" title="{{ __('sessions.revoke_self_disabled') }}">{{ __('sessions.revoke') }}</span>
                                        @else
                                            <form method="POST"
                                                action="{{ route('dashboard.sessions.destroy', $session->id) }}?type=web"
                                                x-data
                                                @submit.prevent="if(confirm('{{ __('sessions.revoke_web_confirm', ['user' => $session->user_name]) }}')) $el.submit()">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-ghost btn-xs text-error active:scale-95">{{ __('sessions.revoke') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
