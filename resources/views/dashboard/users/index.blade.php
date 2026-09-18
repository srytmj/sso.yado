@extends('layouts.dashboard')
@section('title', __('users.title'))
@section('heading', __('users.title'))

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('dashboard.users.invite') }}" class="btn btn-neutral btn-sm active:scale-95">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
            {{ __('users.invite_button') }}
        </a>
    </div>

    <div class="card bg-base-100 border border-base-300">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>{{ __('users.th_user') }}</th>
                        <th class="hidden sm:table-cell">{{ __('users.th_role') }}</th>
                        <th>{{ __('users.th_status') }}</th>
                        <th class="hidden md:table-cell">{{ __('users.th_joined') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <p class="text-sm font-medium text-base-content">{{ $user->name }}</p>
                                <p class="text-xs text-base-content/60">{{ $user->email }}</p>
                            </td>
                            <td class="hidden sm:table-cell">
                                <form method="POST" action="{{ route('dashboard.users.role', $user->id) }}">
                                    @csrf @method('PATCH')
                                    <div x-data="{
                                            open: false,
                                            value: {{ $user->role_id }},
                                            options: [
                                                @foreach($roles as $role)
                                                    { value: {{ $role->id }}, label: @js($role->name) },
                                                @endforeach
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
                                         class="relative w-32">
                                        <input type="hidden" name="role_id" :value="value">
                                        <button type="button" @click="open = !open"
                                                class="btn btn-outline btn-xs w-full flex items-center justify-between gap-1 active:scale-95">
                                            <span x-text="current.label"></span>
                                            <svg class="w-3.5 h-3.5 text-base-content/60 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''"
                                                 fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="menu absolute z-10 mt-1 w-full bg-base-100 border border-base-300 rounded-box shadow-md py-1">
                                            <template x-for="opt in options" :key="opt.value">
                                                <button type="button" @click="select(opt)"
                                                        class="w-full text-left px-2.5 py-1.5 text-xs rounded-lg hover:bg-base-200 transition-colors"
                                                        :class="value === opt.value ? 'text-base-content font-medium bg-base-200' : 'text-base-content/70'">
                                                    <span x-text="opt.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </form>
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge badge-success badge-soft">{{ __('users.active') }}</span>
                                @else
                                    <span class="badge badge-error badge-soft">{{ __('users.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-base-content/60 text-xs hidden md:table-cell">{{ $user->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="flex gap-2 items-center">
                                    <form method="POST" action="{{ route('dashboard.users.toggle-active', $user->id) }}">
                                        @csrf @method('PATCH')
                                        @if($user->is_active)
                                            <button type="submit" class="btn btn-error btn-xs active:scale-95">{{ __('users.deactivate') }}</button>
                                        @else
                                            <button type="submit" class="btn btn-ghost btn-xs active:scale-95">{{ __('users.activate') }}</button>
                                        @endif
                                    </form>

                                    <!-- Change Password Dropdown -->
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open" class="btn btn-outline btn-xs active:scale-95">Pass</button>
                                        <div x-show="open" @click.outside="open = false" x-cloak
                                             class="absolute right-0 z-20 mt-1 w-64 bg-base-100 border border-base-300 rounded-box shadow-lg p-3">
                                            <form method="POST" action="{{ route('dashboard.users.password', $user->id) }}">
                                                @csrf @method('PATCH')
                                                <div class="form-control mb-2">
                                                    <input type="password" name="new_password" placeholder="New Password" required minlength="8" class="input input-bordered input-sm w-full" />
                                                </div>
                                                <div class="form-control mb-3">
                                                    <input type="password" name="new_password_confirmation" placeholder="Confirm Password" required minlength="8" class="input input-bordered input-sm w-full" />
                                                </div>
                                                <button type="submit" class="btn btn-neutral btn-sm w-full">Ganti</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8">
                                <p class="text-sm text-base-content/60">{{ __('users.no_users') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-base-200">{{ $users->links() }}</div>
    </div>
@endsection
