<script>
    import { onMount } from 'svelte';
    import { page } from '@inertiajs/svelte';

    const THEME_MAP = { light: 'yado', dark: 'yado-dark' };

    let mode = 'system'; // 'system' | 'light' | 'dark'

    function resolveSystemMode() {
        if (typeof window === 'undefined') return 'light';
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function apply(nextMode) {
        mode = nextMode;
        const resolved = nextMode === 'system' ? resolveSystemMode() : nextMode;
        document.documentElement.setAttribute('data-theme', THEME_MAP[resolved]);
        try {
            localStorage.setItem('theme', nextMode);
        } catch {}
    }

    function setMode(nextMode) {
        apply(nextMode);

        const authUser = $page.props.auth?.user;
        if (authUser) {
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '');

            fetch('/account/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': token,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ theme: nextMode }),
            }).catch(() => {});
        }
    }

    onMount(() => {
        let stored = null;
        try {
            stored = localStorage.getItem('theme');
        } catch {}

        const authUser = $page.props.auth?.user;
        const initial = stored || authUser?.theme || 'system';
        apply(initial);

        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        const onChange = () => {
            if (mode === 'system') apply('system');
        };
        mq.addEventListener('change', onChange);
        return () => mq.removeEventListener('change', onChange);
    });
</script>

<div class="join">
    <button
        type="button"
        class="join-item btn btn-xs {mode === 'light' ? 'btn-neutral' : 'btn-ghost'}"
        title="Light theme"
        aria-label="Light theme"
        on:click={() => setMode('light')}
    >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-6.364-.386 1.591-1.591M3 12h2.25m.386-6.364 1.591 1.591M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
    </button>
    <button
        type="button"
        class="join-item btn btn-xs {mode === 'system' ? 'btn-neutral' : 'btn-ghost'}"
        title="System theme"
        aria-label="System theme"
        on:click={() => setMode('system')}
    >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
        </svg>
    </button>
    <button
        type="button"
        class="join-item btn btn-xs {mode === 'dark' ? 'btn-neutral' : 'btn-ghost'}"
        title="Dark theme"
        aria-label="Dark theme"
        on:click={() => setMode('dark')}
    >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
    </button>
</div>
