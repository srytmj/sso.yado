<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, router } from '@inertiajs/svelte';
    import DashboardLayout from '../../../Layouts/DashboardLayout.svelte';

    export let entries = { data: [], links: [] };
    export let events = [];
    export let event = '';
    export let search = '';

    let searchQuery = search || '';
    let selectedEvent = event || '';
    let container;

    onMount(() => {
        if (container) {
            gsap.from(container, {
                opacity: 0,
                y: 15,
                duration: 0.5,
                ease: 'power2.out'
            });
        }
    });

    function applyFilter() {
        router.get('/dashboard/audit-log', {
            q: searchQuery || undefined,
            event: selectedEvent || undefined
        }, {
            preserveState: true,
            replace: true
        });
    }

    function resetFilter() {
        searchQuery = '';
        selectedEvent = '';
        router.get('/dashboard/audit-log');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleString('en-GB', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
</script>

<DashboardLayout title="Audit Log">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-base-content">Security & Audit Log</h2>
        <p class="text-xs text-base-content/50 mt-0.5">Immutable record of security events, administrative changes, and user activities</p>
    </div>

    <!-- Filter Bar -->
    <div class="card bg-base-100 border border-base-300 shadow-sm p-4 mb-6 rounded-2xl">
        <form on:submit|preventDefault={applyFilter} class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full relative">
                <input
                    type="text"
                    bind:value={searchQuery}
                    placeholder="Search audit descriptions or IP..."
                    class="input input-bordered input-sm w-full rounded-xl bg-base-100 pl-9"
                />
                <svg class="w-4 h-4 text-base-content/40 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <select
                bind:value={selectedEvent}
                on:change={applyFilter}
                class="select select-bordered select-sm rounded-xl w-full sm:w-52 bg-base-100 text-xs font-medium"
            >
                <option value="">All Events</option>
                {#each events as ev}
                    <option value={ev}>{ev}</option>
                {/each}
            </select>

            <button type="submit" class="btn btn-neutral btn-sm rounded-xl px-4 w-full sm:w-auto">
                Filter
            </button>

            {#if searchQuery || selectedEvent}
                <button
                    type="button"
                    on:click={resetFilter}
                    class="btn btn-ghost btn-sm rounded-xl text-base-content/60 hover:text-base-content w-full sm:w-auto"
                >
                    Reset
                </button>
            {/if}
        </form>
    </div>

    <!-- Table Container -->
    <div bind:this={container} class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden rounded-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-base-200 bg-base-200/30">
            <p class="text-xs font-semibold text-base-content/60 uppercase tracking-wider">
                {entries.total || entries.data?.length || 0} Audit Entries
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead class="bg-base-200/50 text-base-content/60 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-6">Timestamp</th>
                        <th class="py-3 px-4">Event</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 hidden md:table-cell">Actor</th>
                        <th class="py-3 px-6 hidden lg:table-cell">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    {#if !entries.data || entries.data.length === 0}
                        <tr>
                            <td colspan="5" class="text-center py-16 text-base-content/50 text-sm">
                                No audit records found.
                            </td>
                        </tr>
                    {:else}
                        {#each entries.data as entry}
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <td class="py-4 px-6 text-xs text-base-content/60 whitespace-nowrap font-mono">
                                    {formatDate(entry.created_at)}
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="badge badge-neutral badge-soft font-mono text-[11px] px-2 py-1">
                                        {entry.event}
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-sm text-base-content max-w-md">
                                    {entry.description}
                                </td>

                                <td class="py-4 px-4 text-xs text-base-content/70 hidden md:table-cell whitespace-nowrap">
                                    {entry.actor?.name || 'System'}
                                </td>

                                <td class="py-4 px-6 text-xs font-mono text-base-content/50 hidden lg:table-cell whitespace-nowrap">
                                    {entry.ip_address || '-'}
                                </td>
                            </tr>
                        {/each}
                    {/if}
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        {#if entries.links && entries.links.length > 3}
            <div class="p-4 border-t border-base-200 flex items-center justify-between">
                <p class="text-xs text-base-content/50">
                    Showing {entries.data.length} of {entries.total} entries
                </p>
                <div class="join">
                    {#each entries.links as link}
                        {#if link.url}
                            <Link
                                href={link.url}
                                class="join-item btn btn-xs {link.active ? 'btn-neutral' : 'btn-ghost'}"
                            >
                                {@html link.label}
                            </Link>
                        {:else}
                            <span class="join-item btn btn-xs btn-disabled opacity-40">
                                {@html link.label}
                            </span>
                        {/if}
                    {/each}
                </div>
            </div>
        {/if}
    </div>
</DashboardLayout>
