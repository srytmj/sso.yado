<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, page } from '@inertiajs/svelte';
    import ThemeToggle from '../Components/ThemeToggle.svelte';

    export let title = 'Dashboard';

    let sidebarOpen = false;
    let mainContent;

    $: currentUrl = $page.url;
    $: user = $page.props.auth?.user;
    $: flash = $page.props.flash || {};

    const navItems = [
        {
            name: 'Overview',
            href: '/dashboard',
            pattern: /^\/dashboard$/,
            icon: 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'
        },
        {
            name: 'Applications',
            href: '/dashboard/applications',
            pattern: /^\/dashboard\/applications/,
            icon: 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3'
        },
        {
            name: 'Users',
            href: '/dashboard/users',
            pattern: /^\/dashboard\/users/,
            icon: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'
        },
        {
            name: 'Active Sessions',
            href: '/dashboard/sessions',
            pattern: /^\/dashboard\/sessions/,
            icon: 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'
        },
        {
            name: 'System Logs',
            href: '/dashboard/logs',
            pattern: /^\/dashboard\/logs/,
            icon: 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'
        },
        {
            name: 'Audit Log',
            href: '/dashboard/audit-log',
            pattern: /^\/dashboard\/audit-log/,
            icon: 'M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z M6 6.75h12M6 12.75h12M6 18.75h12'
        },
        {
            name: 'Settings',
            href: '/dashboard/settings',
            pattern: /^\/dashboard\/settings/,
            icon: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'
        }
    ];

    onMount(() => {
        if (mainContent) {
            gsap.from(mainContent, {
                opacity: 0,
                y: 12,
                duration: 0.45,
                ease: 'power3.out'
            });
        }
    });
</script>

<svelte:head>
    <title>{title} - SSO Yado</title>
</svelte:head>

<div class="min-h-screen bg-base-200 text-base-content flex flex-col font-sans">
    <!-- Mobile overlay -->
    {#if sidebarOpen}
        <button
            type="button"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30 lg:hidden cursor-pointer w-full h-full border-0 p-0 m-0 text-left"
            on:click={() => sidebarOpen = false}
            aria-label="Close sidebar"
        ></button>
    {/if}

    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-base-100 border-r border-base-300 z-40 flex flex-col transition-transform duration-300 lg:translate-x-0 {sidebarOpen ? 'translate-x-0' : '-translate-x-full'}">
        <!-- Logo / Brand -->
        <div class="px-6 py-6 border-b border-base-200 flex items-center justify-between">
            <Link href="/" class="flex items-baseline gap-2 group">
                <span class="font-serif italic text-3xl font-bold tracking-tight text-base-content group-hover:opacity-80 transition-opacity">Yado</span>
                <span class="text-[10px] tracking-widest uppercase font-semibold text-base-content/40 bg-base-200 px-1.5 py-0.5 rounded">SSO</span>
            </Link>
            <span class="badge badge-sm badge-neutral font-mono text-[10px] tracking-wider uppercase">Admin</span>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-3 py-5 overflow-y-auto">
            <ul class="space-y-1">
                {#each navItems as item}
                    {@const active = item.pattern.test(currentUrl)}
                    <li>
                        <Link
                            href={item.href}
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {active ? 'bg-neutral text-neutral-content shadow-sm' : 'text-base-content/70 hover:text-base-content hover:bg-base-200'}"
                            on:click={() => sidebarOpen = false}
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d={item.icon} />
                            </svg>
                            <span>{item.name}</span>
                        </Link>
                    </li>
                {/each}
            </ul>
        </nav>

        <!-- User Profile & Footer Actions -->
        <div class="p-4 border-t border-base-200 bg-base-100/50">
            <div class="flex items-center gap-3 px-2 mb-3">
                <div class="w-8 h-8 rounded-full bg-neutral text-neutral-content flex items-center justify-center font-bold text-xs">
                    {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium truncate text-base-content">{user?.name || 'Administrator'}</p>
                    <p class="text-xs text-base-content/50 truncate font-mono">{user?.email || ''}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <Link href="/account" class="btn btn-outline btn-xs rounded-lg font-medium justify-center">
                    Account
                </Link>
                <Link href="/logout" method="post" as="button" class="btn btn-ghost btn-xs rounded-lg text-error hover:bg-error/10 font-medium justify-center">
                    Logout
                </Link>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="lg:pl-64 flex flex-col flex-1 min-h-screen">
        <!-- Top Navbar -->
        <header class="bg-base-100/80 backdrop-blur-md border-b border-base-300 sticky top-0 z-20 px-4 sm:px-8 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="btn btn-ghost btn-square btn-sm lg:hidden text-base-content"
                    on:click={() => sidebarOpen = !sidebarOpen}
                    aria-label="Toggle navigation"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <h1 class="text-base sm:text-lg font-semibold text-base-content tracking-tight">{title}</h1>
            </div>

            <div class="flex items-center gap-3">
                <ThemeToggle />
                <Link href="/" class="btn btn-ghost btn-xs text-base-content/60 hover:text-base-content">
                    Landing Page
                </Link>
            </div>
        </header>

        <!-- Page Body -->
        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto" bind:this={mainContent}>
            <!-- Flash Message Alerts -->
            {#if flash.success}
                <div class="alert alert-success alert-soft mb-6 text-sm shadow-sm rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0 text-success" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>{flash.success}</span>
                </div>
            {/if}

            {#if flash.error}
                <div class="alert alert-error alert-soft mb-6 text-sm shadow-sm rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0 text-error" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>{flash.error}</span>
                </div>
            {/if}

            <slot />
        </main>
    </div>
</div>
