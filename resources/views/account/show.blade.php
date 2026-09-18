@extends('layouts.account')

@section('title', __('account.title'))

@section('content')
    {{-- Profile Info --}}
    <div class="card bg-base-100 border border-base-300 mb-6 animate-fade-in">
        <div class="card-body p-6">
        <h2 class="text-sm font-semibold text-base-content mb-4">{{ __('account.profile_heading') }}</h2>

        <div class="flex items-center gap-4 mb-6">
            @if($user->avatar)
                <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-14 h-14 rounded-full object-cover flex-shrink-0">
            @else
                <div class="avatar avatar-placeholder flex-shrink-0">
                    <div class="bg-primary/10 text-primary w-14 rounded-full">
                        <span class="text-lg font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-sm font-semibold text-base-content">{{ $user->name }}</p>
                <p class="text-sm text-base-content/60 mb-2">{{ '@' . $user->username }}</p>
                <form method="POST" action="{{ route('account.avatar') }}" enctype="multipart/form-data" class="flex items-center gap-2"
                      x-data="{ fileName: '' }">
                    @csrf
                    <label class="cursor-pointer text-xs text-base-content/60 hover:text-base-content underline underline-offset-2">
                        <span x-text="fileName || @js(__('account.change_avatar'))"></span>
                        <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="hidden"
                               @change="fileName = $event.target.files[0]?.name; $el.closest('form').querySelector('button').classList.remove('hidden')">
                    </label>
                    <button type="submit" class="hidden btn btn-neutral btn-xs active:scale-95">{{ __('account.upload_button') }}</button>
                </form>
                @error('avatar')
                    <p class="text-xs text-error mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-base-content/40 mb-1">{{ __('account.email') }}</p>
                <p class="text-sm text-base-content">{{ $user->email }}</p>
            </div>
            <div>
                <p class="text-xs text-base-content/40 mb-1">{{ __('account.role') }}</p>
                <span class="badge badge-neutral badge-soft">{{ $user->role->name ?? '-' }}</span>
            </div>
            <div>
                <p class="text-xs text-base-content/40 mb-1">{{ __('account.email_verification') }}</p>
                @if($user->email_verified_at)
                    <span class="badge badge-success badge-soft">{{ __('account.verified') }}</span>
                @else
                    <span class="badge badge-warning badge-soft">{{ __('account.not_verified') }}</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-base-content/40 mb-1">{{ __('account.account_status') }}</p>
                @if($user->is_active)
                    <span class="badge badge-success badge-soft">{{ __('account.active') }}</span>
                @else
                    <span class="badge badge-error badge-soft">{{ __('account.inactive') }}</span>
                @endif
            </div>
        </div>
        </div>
    </div>

    {{-- Preferences --}}
    <div class="card bg-base-100 border border-base-300 mb-6 animate-fade-in">
        <div class="card-body p-6">
        <h2 class="text-sm font-semibold text-base-content mb-1">{{ __('account.preferences_heading') }}</h2>
        <p class="text-xs text-base-content/40 mb-4">{{ __('account.language_help') }}</p>

        <form method="POST" action="{{ route('account.locale') }}">
            @csrf
            <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('account.language_label') }}</label>
            <div x-data="{
                    open: false,
                    value: @js($user->locale),
                    options: [
                        { value: 'id', label: 'Bahasa Indonesia' },
                        { value: 'en', label: 'English' },
                        { value: 'ja', label: '日本語' },
                    ],
                    get current() { return this.options.find(o => o.value === this.value); },
                    select(opt) {
                        this.open = false;
                        if (opt.value === this.value) return;
                        this.value = opt.value;
                        $el.closest('form').submit();
                    }
                 }"
                 @click.outside="open = false"
                 class="relative w-full sm:w-64">
                <input type="hidden" name="locale" :value="value">
                <button type="button" @click="open = !open"
                        class="btn btn-outline btn-sm w-full flex items-center justify-between gap-2 active:scale-95">
                    <span x-text="current.label"></span>
                    <svg class="w-4 h-4 text-base-content/40 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     x-cloak
                     class="menu absolute z-10 mt-1 w-full bg-base-100 border border-base-300 rounded-box shadow-md py-1">
                    <template x-for="opt in options" :key="opt.value">
                        <button type="button" @click="select(opt)"
                                class="w-full flex items-center justify-between gap-2 text-left px-3 py-2 text-sm rounded-lg hover:bg-base-200"
                                :class="value === opt.value ? 'text-base-content font-medium bg-base-200' : 'text-base-content/60'">
                            <span x-text="opt.label"></span>
                            <svg x-show="value === opt.value" class="w-3.5 h-3.5 text-base-content shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </form>
        </div>
    </div>

    {{-- Change Password --}}
    <div class="card bg-base-100 border border-base-300 animate-fade-in">
        <div class="card-body p-6">
        <h2 class="text-sm font-semibold text-base-content mb-4">{{ __('account.change_password_heading') }}</h2>

        <form method="POST" action="{{ route('account.password') }}" novalidate>
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('account.current_password') }}</label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="current_password" autocomplete="current-password"
                            class="input input-bordered w-full pr-10" />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content/70">
                            <svg x-show="!show" x-transition.opacity.duration.150ms class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            <svg x-show="show" x-transition.opacity.duration.150ms class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('account.new_password') }}</label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="new_password" autocomplete="new-password"
                            class="input input-bordered w-full pr-10" />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content/70">
                            <svg x-show="!show" x-transition.opacity.duration.150ms class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            <svg x-show="show" x-transition.opacity.duration.150ms class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                        </button>
                    </div>
                    @error('new_password')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-base-content/70 mb-1">{{ __('account.confirm_new_password') }}</label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="new_password_confirmation" autocomplete="new-password"
                            class="input input-bordered w-full pr-10" />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content/70">
                            <svg x-show="!show" x-transition.opacity.duration.150ms class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            <svg x-show="show" x-transition.opacity.duration.150ms class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-neutral active:scale-95">{{ __('account.update_password_button') }}</button>
            </div>
        </form>
        </div>
    </div>
@endsection
