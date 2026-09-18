<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link } from '@inertiajs/svelte';
    import DashboardLayout from '../../Layouts/DashboardLayout.svelte';

    export let stats = { users_active: 0, users_total: 0, clients: 0 };
    export let clients = [];

    let statsContainer;
    let cardsContainer;

    onMount(() => {
        if (statsContainer) {
            gsap.from(statsContainer.children, {
                opacity: 0,
                y: 15,
                duration: 0.5,
                stagger: 0.08,
                ease: 'power2.out'
            });
        }
        if (cardsContainer) {
            gsap.from(cardsContainer.children, {
                opacity: 0,
                y: 20,
                duration: 0.6,
                delay: 0.15,
                stagger: 0.1,
                ease: 'power3.out'
            });
        }
    });
</script>

<DashboardLayout title="Overview">
    <!-- Stats Cards -->
    <div bind:this={statsContainer} class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body p-6">
                <p class="text-xs font-semibold tracking-wider text-base-content/50 uppercase">Active Users</p>
                <p class="text-4xl font-bold tracking-tight text-base-content mt-1">{stats.users_active}</p>
                <p class="text-xs text-base-content/60 mt-1">out of {stats.users_total} registered users</p>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body p-6">
                <p class="text-xs font-semibold tracking-wider text-base-content/50 uppercase">Registered Apps</p>
                <p class="text-4xl font-bold tracking-tight text-base-content mt-1">{stats.clients}</p>
                <p class="text-xs text-base-content/60 mt-1">Connected OAuth2 clients</p>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body p-6">
                <p class="text-xs font-semibold tracking-wider text-base-content/50 uppercase">OAuth Standard</p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="badge badge-neutral font-mono text-xs">PKCE S256</span>
                </div>
                <p class="text-xs text-base-content/60 mt-2">Auth Code flow with PKCE enforced</p>
            </div>
        </div>
    </div>

    <!-- Main Grid: Apps and Quick Actions -->
    <div bind:this={cardsContainer} class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Registered Applications List -->
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-base-200">
                    <div>
                        <h2 class="text-base font-semibold text-base-content">Registered Applications</h2>
                        <p class="text-xs text-base-content/50 mt-0.5">Connected microservices and clients</p>
                    </div>
                    <Link href="/dashboard/applications/create" class="btn btn-neutral btn-xs rounded-lg">
                        + Add App
                    </Link>
                </div>

                {#if !clients || clients.length === 0}
                    <div class="text-center py-12 text-base-content/50 text-sm">
                        No applications registered yet.
                    </div>
                {:else}
                    <div class="divide-y divide-base-200">
                        {#each clients as client}
                            <div class="flex items-center justify-between py-3 group">
                                <div class="min-w-0 pr-4">
                                    <p class="text-sm font-medium text-base-content group-hover:underline truncate">{client.name}</p>
                                    <p class="text-xs text-base-content/50 font-mono mt-0.5 truncate">{client.redirect || '-'}</p>
                                </div>
                                <Link href="/dashboard/applications/{client.id}" class="btn btn-outline btn-xs rounded-lg shrink-0">
                                    Detail
                                </Link>
                            </div>
                        {/each}
                    </div>
                {/if}
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body p-6">
                <div class="mb-4 pb-3 border-b border-base-200">
                    <h2 class="text-base font-semibold text-base-content">Quick Actions</h2>
                    <p class="text-xs text-base-content/50 mt-0.5">Common administrative tasks</p>
                </div>

                <div class="space-y-2">
                    <Link href="/dashboard/applications/create" class="flex items-center gap-3 p-3 rounded-xl hover:bg-base-200 transition-colors border border-transparent hover:border-base-300 group">
                        <div class="w-8 h-8 rounded-lg bg-neutral/10 text-base-content flex items-center justify-center shrink-0 group-hover:bg-neutral group-hover:text-neutral-content transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-base-content">Register New App</p>
                            <p class="text-xs text-base-content/50">Add a new OAuth2 client application</p>
                        </div>
                    </Link>

                    <Link href="/dashboard/applications" class="flex items-center gap-3 p-3 rounded-xl hover:bg-base-200 transition-colors border border-transparent hover:border-base-300 group">
                        <div class="w-8 h-8 rounded-lg bg-neutral/10 text-base-content flex items-center justify-center shrink-0 group-hover:bg-neutral group-hover:text-neutral-content transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-base-content">Manage Applications</p>
                            <p class="text-xs text-base-content/50">View, edit, or revoke existing client apps</p>
                        </div>
                    </Link>

                    <Link href="/dashboard/users/invite" class="flex items-center gap-3 p-3 rounded-xl hover:bg-base-200 transition-colors border border-transparent hover:border-base-300 group">
                        <div class="w-8 h-8 rounded-lg bg-neutral/10 text-base-content flex items-center justify-center shrink-0 group-hover:bg-neutral group-hover:text-neutral-content transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-base-content">Invite User</p>
                            <p class="text-xs text-base-content/50">Send an invitation email to a new member</p>
                        </div>
                    </Link>

                    <Link href="/dashboard/users" class="flex items-center gap-3 p-3 rounded-xl hover:bg-base-200 transition-colors border border-transparent hover:border-base-300 group">
                        <div class="w-8 h-8 rounded-lg bg-neutral/10 text-base-content flex items-center justify-center shrink-0 group-hover:bg-neutral group-hover:text-neutral-content transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-base-content">Manage Users</p>
                            <p class="text-xs text-base-content/50">Activate, assign roles, or reset passwords</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</DashboardLayout>
