<div x-data="{
        theme: document.documentElement.dataset.themePref || 'system',
        cycle() {
            const order = ['system', 'light', 'dark'];
            this.theme = order[(order.indexOf(this.theme) + 1) % order.length];
            localStorage.setItem('theme', this.theme);
            const isDark = this.theme === 'dark' || (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.dataset.theme = isDark ? 'yado-dark' : 'yado';
            document.documentElement.dataset.themePref = this.theme;
            @auth
            fetch('{{ route('account.theme') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ theme: this.theme }),
            });
            @endauth
        }
     }"
     class="inline-flex">
    <button type="button" @click="cycle()"
            :title="theme === 'system' ? '{{ __('common.theme_system') }}' : (theme === 'light' ? '{{ __('common.theme_light') }}' : '{{ __('common.theme_dark') }}')"
            class="btn btn-ghost btn-square btn-sm">
        {{-- Wrapper posisi relatif ukuran tetap - icon di-stack absolute di dalamnya
             supaya crossfade gak nabrak/nge-reflow tombol pas ganti icon. x-show/
             x-transition/transform dipasang di <span> (elemen HTML), bukan langsung
             di <svg> - properti transform individual (scale/rotate) kadang gak
             konsisten kalau ditempel langsung ke elemen SVG. --}}
        <span x-cloak class="relative w-4 h-4 inline-block">
            <span x-show="theme === 'system'"
                 x-transition:enter="transition-[scale,rotate,opacity] ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-50 -rotate-45"
                 x-transition:enter-end="opacity-100 scale-100 rotate-0"
                 x-transition:leave="transition-[scale,rotate,opacity] ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 rotate-0"
                 x-transition:leave-end="opacity-0 scale-50 rotate-45"
                 class="absolute inset-0 w-4 h-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3" />
                </svg>
            </span>
            <span x-show="theme === 'light'"
                 x-transition:enter="transition-[scale,rotate,opacity] ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-50 -rotate-45"
                 x-transition:enter-end="opacity-100 scale-100 rotate-0"
                 x-transition:leave="transition-[scale,rotate,opacity] ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 rotate-0"
                 x-transition:leave-end="opacity-0 scale-50 rotate-45"
                 class="absolute inset-0 w-4 h-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                </svg>
            </span>
            <span x-show="theme === 'dark'"
                 x-transition:enter="transition-[scale,rotate,opacity] ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-50 -rotate-45"
                 x-transition:enter-end="opacity-100 scale-100 rotate-0"
                 x-transition:leave="transition-[scale,rotate,opacity] ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 rotate-0"
                 x-transition:leave-end="opacity-0 scale-50 rotate-45"
                 class="absolute inset-0 w-4 h-4">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>
            </span>
        </span>
    </button>
</div>
