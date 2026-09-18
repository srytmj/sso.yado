@extends('layouts.account')

@section('title', __('two_factor.title'))

@section('content')
    <div class="card bg-base-100 border border-base-300 mb-6 animate-fade-in">
        <div class="card-body p-6">
        <div class="flex items-start justify-between mb-1">
            <div>
                <h2 class="text-sm font-semibold text-base-content">{{ __('two_factor.title') }}</h2>
                <p class="text-sm text-base-content/60 mt-0.5">{{ __('two_factor.description') }}</p>
            </div>
            <span class="badge badge-soft shrink-0 {{ $user->hasTwoFactorEnabled() ? 'badge-success' : 'badge-neutral' }}">
                {{ $user->hasTwoFactorEnabled() ? __('two_factor.status_enabled') : __('two_factor.status_disabled') }}
            </span>
        </div>

        <hr class="border-base-300 my-4">

        @if($recoveryCodes)
            {{-- Recovery codes - shown once --}}
            <div class="alert alert-warning alert-soft flex-col items-start mb-4">
                <p class="text-xs font-semibold mb-1">{{ __('two_factor.recovery_codes_heading') }}</p>
                <p class="text-xs mb-3">{{ __('two_factor.recovery_codes_warning') }}</p>
                <div class="grid grid-cols-2 gap-2 font-mono text-sm w-full">
                    @foreach($recoveryCodes as $code)
                        <code class="bg-base-100 border border-warning/30 rounded-lg px-3 py-2">{{ $code }}</code>
                    @endforeach
                </div>
            </div>
        @endif

        @if($qrCodeSvg)
            {{-- Setup in progress: show QR + confirm form --}}
            <div class="max-w-sm">
                <p class="text-sm font-medium text-base-content mb-1">{{ __('two_factor.setup_heading') }}</p>
                <p class="text-xs text-base-content/60 mb-4">{{ __('two_factor.setup_instruction') }}</p>
                {{-- bg-white sengaja fixed (bukan token daisyUI) - QR code butuh kontras hitam-di-atas-putih
                     yang konsisten supaya kamera authenticator app bisa scan dengan andal --}}
                <div class="bg-white p-4 rounded-lg border border-base-300 inline-block mb-3">
                    {!! $qrCodeSvg !!}
                </div>
                <p class="text-xs text-base-content/40 mb-1">{{ __('two_factor.manual_key_label') }}</p>
                <code class="block text-xs font-mono text-base-content/70 bg-base-200 rounded-lg px-3 py-2 mb-4 break-all">{{ $manualKey }}</code>

                <form method="POST" action="{{ route('account.two-factor.confirm') }}">
                    @csrf
                    <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('two_factor.code_label') }}</label>
                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus
                        class="input input-bordered w-full" />
                    @error('code')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="btn btn-neutral mt-4 active:scale-95">{{ __('two_factor.confirm_button') }}</button>
                </form>
            </div>
        @elseif($user->hasTwoFactorEnabled())
            <form method="POST" action="{{ route('account.two-factor.disable') }}" class="max-w-sm">
                @csrf @method('DELETE')
                <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('two_factor.confirm_password_label') }}</label>
                <input type="password" name="current_password"
                    class="input input-bordered w-full" />
                @error('current_password')
                    <p class="text-xs text-error mt-1">{{ $message }}</p>
                @enderror
                <button type="submit" class="btn btn-error mt-4 active:scale-95">{{ __('two_factor.disable_button') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('account.two-factor.enable') }}" class="max-w-sm">
                @csrf
                <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('two_factor.confirm_password_label') }}</label>
                <input type="password" name="current_password"
                    class="input input-bordered w-full" />
                @error('current_password')
                    <p class="text-xs text-error mt-1">{{ $message }}</p>
                @enderror
                <button type="submit" class="btn btn-neutral mt-4 active:scale-95">{{ __('two_factor.enable_button') }}</button>
            </form>
        @endif
        </div>
    </div>
@endsection
