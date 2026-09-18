@extends('layouts.dashboard')
@section('title', __('audit.title'))
@section('heading', __('audit.title'))

@section('content')
    <div class="card bg-base-100 border border-base-300 mb-6">
        <div class="card-body p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <form method="GET" class="flex-1">
                    <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('audit.search_placeholder') }}"
                           class="input w-full">
                    @if($event)
                        <input type="hidden" name="event" value="{{ $event }}">
                    @endif
                </form>

                <div x-data="{
                        open: false,
                        value: @js($event ?? ''),
                        options: [
                            { value: '', label: @js(__('audit.all_events')) },
                            @foreach($events as $ev)
                                { value: @js($ev), label: @js($ev) },
                            @endforeach
                        ],
                        get current() { return this.options.find(o => o.value === this.value) ?? this.options[0]; },
                        select(opt) {
                            this.open = false;
                            if (opt.value === this.value) return;
                            this.value = opt.value;
                            const url = new URL(window.location.href);
                            if (opt.value) { url.searchParams.set('event', opt.value); } else { url.searchParams.delete('event'); }
                            url.searchParams.delete('page');
                            window.location.href = url.toString();
                        }
                     }"
                     @click.outside="open = false"
                     class="relative w-full sm:w-64 shrink-0">
                    <button type="button" @click="open = !open"
                            class="btn btn-outline w-full flex items-center justify-between gap-2 active:scale-95">
                        <span x-text="current.label" class="truncate"></span>
                        <svg class="w-4 h-4 text-base-content/60 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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
                         class="menu absolute z-10 mt-1 w-full bg-base-100 border border-base-300 rounded-box shadow-md py-1 max-h-64 overflow-y-auto flex-nowrap">
                        <template x-for="opt in options" :key="opt.value">
                            <button type="button" @click="select(opt)"
                                    class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-base-200 transition-colors"
                                    :class="value === opt.value ? 'text-base-content font-medium bg-base-200' : 'text-base-content/70'">
                                <span x-text="opt.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                @if($event || $search)
                    <a href="{{ route('dashboard.audit-log.index') }}" class="px-3 py-2 text-sm text-base-content/60 hover:text-base-content transition-colors shrink-0 self-center">
                        {{ __('common.reset') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="card bg-base-100 border border-base-300">
        <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
            <p class="text-sm text-base-content/60">{{ __('audit.entries_found', ['count' => $entries->total()]) }}</p>
        </div>

        @if($entries->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm text-base-content/60">{{ __('audit.no_match') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>{{ __('audit.th_time') }}</th>
                            <th>{{ __('audit.th_event') }}</th>
                            <th>{{ __('audit.th_description') }}</th>
                            <th class="hidden md:table-cell">{{ __('audit.th_actor') }}</th>
                            <th class="hidden lg:table-cell">{{ __('audit.th_ip') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr>
                                <td class="text-xs text-base-content/60 whitespace-nowrap">{{ $entry->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <span class="badge badge-neutral badge-soft font-mono">{{ $entry->event }}</span>
                                </td>
                                <td class="text-base-content">{{ $entry->description }}</td>
                                <td class="text-base-content/60 hidden md:table-cell">{{ $entry->actor?->name ?? __('audit.system') }}</td>
                                <td class="text-base-content/60 hidden lg:table-cell font-mono text-xs">{{ $entry->ip_address ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-base-200">{{ $entries->links() }}</div>
        @endif
    </div>
@endsection
