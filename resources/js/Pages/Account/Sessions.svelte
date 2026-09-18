<script>
    import { router } from '@inertiajs/svelte';
    import AccountLayout from '../../Layouts/AccountLayout.svelte';

    export let tokens = [];
    export let devices = [];
    export let currentSessionId = '';

    function revokeToken(tokenId) {
        if (confirm('Revoke access for this application?')) {
            router.delete(`/account/sessions/${tokenId}`);
        }
    }

    function revokeAllTokens() {
        if (confirm('Revoke access for all connected applications?')) {
            router.delete('/account/sessions');
        }
    }

    function revokeDevice(sessionId) {
        if (confirm('Sign out from this device?')) {
            router.delete(`/account/devices/${sessionId}`);
        }
    }

    function revokeAllDevices() {
        if (confirm('Sign out from all other devices?')) {
            router.delete('/account/devices');
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
</script>

<AccountLayout title="Active Sessions">
    <div class="space-y-8">
        <!-- Connected Apps / OAuth Tokens -->
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
            <div class="card-body p-6 sm:p-8">
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-base-200">
                    <div>
                        <h3 class="text-base font-semibold text-base-content">Connected Applications</h3>
                        <p class="text-xs text-base-content/50 mt-0.5">Services authorized to access your Yado account</p>
                    </div>
                    {#if tokens.length > 0}
                        <button
                            type="button"
                            on:click={revokeAllTokens}
                            class="btn btn-ghost btn-xs text-error hover:bg-error/10 rounded-lg"
                        >
                            Revoke All
                        </button>
                    {/if}
                </div>

                {#if !tokens || tokens.length === 0}
                    <div class="text-center py-10 text-base-content/50 text-xs sm:text-sm">
                        No connected applications found.
                    </div>
                {:else}
                    <div class="divide-y divide-base-200">
                        {#each tokens as token}
                            <div class="flex items-center justify-between py-3.5">
                                <div class="min-w-0 pr-4">
                                    <p class="text-sm font-medium text-base-content">{token.client?.name || 'Authorized Client'}</p>
                                    <p class="text-xs text-base-content/40 mt-0.5">
                                        Authorized {timeAgo(token.created_at)}
                                    </p>
                                    {#if token.scopes && token.scopes.length > 0}
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            {#each token.scopes as scope}
                                                <span class="badge badge-neutral badge-soft badge-xs font-mono">{scope}</span>
                                            {/each}
                                        </div>
                                    {/if}
                                </div>
                                <button
                                    type="button"
                                    on:click={() => revokeToken(token.id)}
                                    class="btn btn-outline btn-xs rounded-lg shrink-0 text-error hover:bg-error/10 hover:border-error/30"
                                >
                                    Revoke
                                </button>
                            </div>
                        {/each}
                    </div>
                {/if}
            </div>
        </div>

        <!-- Active Devices & Browser Sessions -->
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
            <div class="card-body p-6 sm:p-8">
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-base-200">
                    <div>
                        <h3 class="text-base font-semibold text-base-content">Web Browser Sessions</h3>
                        <p class="text-xs text-base-content/50 mt-0.5">Devices currently signed into this account</p>
                    </div>
                    {#if devices.length > 1}
                        <button
                            type="button"
                            on:click={revokeAllDevices}
                            class="btn btn-ghost btn-xs text-error hover:bg-error/10 rounded-lg"
                        >
                            Sign Out Others
                        </button>
                    {/if}
                </div>

                {#if !devices || devices.length === 0}
                    <div class="text-center py-10 text-base-content/50 text-xs sm:text-sm">
                        No active web sessions.
                    </div>
                {:else}
                    <div class="divide-y divide-base-200">
                        {#each devices as device}
                            {@const isCurrent = device.id === currentSessionId}
                            <div class="flex items-center justify-between py-3.5">
                                <div class="min-w-0 pr-4">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-base-content">{device.ip_address || 'Unknown Device'}</p>
                                        {#if isCurrent}
                                            <span class="badge badge-success badge-soft badge-xs">This device</span>
                                        {/if}
                                    </div>
                                    <p class="text-xs text-base-content/40 mt-0.5">
                                        Last active {timeAgo(device.last_activity)}
                                    </p>
                                </div>

                                {#if !isCurrent}
                                    <button
                                        type="button"
                                        on:click={() => revokeDevice(device.id)}
                                        class="btn btn-ghost btn-xs text-error hover:bg-error/10 rounded-lg shrink-0"
                                    >
                                        Sign Out
                                    </button>
                                {/if}
                            </div>
                        {/each}
                    </div>
                {/if}
            </div>
        </div>
    </div>
</AccountLayout>
