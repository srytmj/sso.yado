<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { router } from '@inertiajs/svelte';
    import DashboardLayout from '../../../Layouts/DashboardLayout.svelte';

    export let oauthSessions = [];
    export let webSessions = [];

    let container;
    let revokingId = null;

    onMount(() => {
        if (container) {
            gsap.from(container.children, {
                opacity: 0,
                y: 15,
                duration: 0.5,
                stagger: 0.1,
                ease: 'power2.out'
            });
        }
    });

    function revokeOAuth(session) {
        if (confirm(`Revoke OAuth session for "${session.user_name}" on application "${session.client_name}"?`)) {
            revokingId = session.id;
            router.delete(`/dashboard/sessions/${session.id}?type=oauth`, {
                onFinish: () => revokingId = null
            });
        }
    }

    function revokeWeb(session) {
        if (confirm(`Revoke web session for user "${session.user_name}"?`)) {
            revokingId = session.id;
            router.delete(`/dashboard/sessions/${session.id}?type=web`, {
                onFinish: () => revokingId = null
            });
        }
    }

    function timeAgo(dateInput) {
        if (!dateInput) return '-';
        const date = typeof dateInput === 'number' ? new Date(dateInput * 1000) : new Date(dateInput);
        const seconds = Math.floor((new Date() - date) / 1000);
        if (seconds < 60) return `${Math.max(1, seconds)}s ago`;
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        const days = Math.floor(hours / 24);
        return `${days}d ago`;
    }

    function expiresLabel(expiresAtStr) {
        if (!expiresAtStr) return '-';
        const exp = new Date(expiresAtStr);
        const diffMin = Math.floor((exp - new Date()) / 60000);
        if (diffMin <= 0) return { label: 'Expired', badge: 'badge-error' };
        if (diffMin < 10) return { label: 'Expiring soon', badge: 'badge-warning' };
        return { label: `in ${Math.floor(diffMin / 60)}h ${diffMin % 60}m`, badge: null };
    }
</script>

<DashboardLayout title="Active Sessions">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-base-content">Session Management</h2>
        <p class="text-xs text-base-content/50 mt-0.5">Monitor and revoke active SSO web sessions and OAuth2 application grants</p>
    </div>

    <div bind:this={container} class="space-y-8">
        <!-- OAuth Sessions Table -->
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-base-content">OAuth2 Application Sessions</h3>
                <span class="badge badge-neutral badge-sm font-mono">{oauthSessions.length}</span>
            </div>
            <p class="text-xs text-base-content/60">Active tokens granted to third-party or internal applications.</p>

            <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead class="bg-base-200/50 text-base-content/60 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-6">User</th>
                                <th class="py-3 px-4">Application</th>
                                <th class="py-3 px-4 hidden md:table-cell">Granted</th>
                                <th class="py-3 px-4 hidden lg:table-cell">Expires</th>
                                <th class="py-3 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            {#if !oauthSessions || oauthSessions.length === 0}
                                <tr>
                                    <td colspan="5" class="text-center py-12 text-base-content/50 text-sm">
                                        No active OAuth sessions found.
                                    </td>
                                </tr>
                            {:else}
                                {#each oauthSessions as session}
                                    {@const expInfo = expiresLabel(session.expires_at)}
                                    <tr class="hover:bg-base-200/40 transition-colors">
                                        <td class="py-4 px-6">
                                            <p class="text-sm font-medium text-base-content">{session.user_name}</p>
                                            <p class="text-xs text-base-content/50 font-mono">{session.user_email}</p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="badge badge-neutral badge-outline badge-sm font-medium">
                                                {session.client_name}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-xs text-base-content/60 hidden md:table-cell whitespace-nowrap">
                                            {timeAgo(session.created_at)}
                                        </td>
                                        <td class="py-4 px-4 text-xs text-base-content/60 hidden lg:table-cell whitespace-nowrap">
                                            {#if expInfo.badge}
                                                <span class="badge {expInfo.badge} badge-xs font-medium">{expInfo.label}</span>
                                            {:else}
                                                {expInfo.label}
                                            {/if}
                                        </td>
                                        <td class="py-4 px-6 text-right whitespace-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-ghost btn-xs text-error hover:bg-error/10 rounded-lg"
                                                disabled={revokingId === session.id}
                                                on:click={() => revokeOAuth(session)}
                                            >
                                                {#if revokingId === session.id}
                                                    <span class="loading loading-spinner loading-xs"></span>
                                                {:else}
                                                    Revoke
                                                {/if}
                                            </button>
                                        </td>
                                    </tr>
                                {/each}
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Web Sessions Table -->
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-base-content">SSO Web Portal Sessions</h3>
                <span class="badge badge-neutral badge-sm font-mono">{webSessions.length}</span>
            </div>
            <p class="text-xs text-base-content/60">Active browser sessions on the SSO login dashboard.</p>

            <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead class="bg-base-200/50 text-base-content/60 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-6">User</th>
                                <th class="py-3 px-4 hidden md:table-cell">IP Address</th>
                                <th class="py-3 px-4 hidden lg:table-cell">Last Active</th>
                                <th class="py-3 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            {#if !webSessions || webSessions.length === 0}
                                <tr>
                                    <td colspan="4" class="text-center py-12 text-base-content/50 text-sm">
                                        No active web sessions.
                                    </td>
                                </tr>
                            {:else}
                                {#each webSessions as session}
                                    <tr class="hover:bg-base-200/40 transition-colors">
                                        <td class="py-4 px-6">
                                            <p class="text-sm font-medium text-base-content">{session.user_name}</p>
                                            <p class="text-xs text-base-content/50 font-mono">{session.user_email}</p>
                                        </td>
                                        <td class="py-4 px-4 font-mono text-xs text-base-content/60 hidden md:table-cell">
                                            {session.ip_address || '-'}
                                        </td>
                                        <td class="py-4 px-4 text-xs text-base-content/60 hidden lg:table-cell whitespace-nowrap">
                                            {timeAgo(session.last_activity)}
                                        </td>
                                        <td class="py-4 px-6 text-right whitespace-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-ghost btn-xs text-error hover:bg-error/10 rounded-lg"
                                                disabled={revokingId === session.id}
                                                on:click={() => revokeWeb(session)}
                                            >
                                                {#if revokingId === session.id}
                                                    <span class="loading loading-spinner loading-xs"></span>
                                                {:else}
                                                    Revoke
                                                {/if}
                                            </button>
                                        </td>
                                    </tr>
                                {/each}
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</DashboardLayout>
