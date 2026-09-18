<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, router } from '@inertiajs/svelte';
    import DashboardLayout from '../../../Layouts/DashboardLayout.svelte';

    export let entries = { data: [], links: [] };
    export let levels = [];
    export let level = '';
    export let search = '';

    let searchQuery = search || '';
    let selectedLevel = level || '';
    let container;
    let expandedIndices = {};

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

    function toggleExpand(index) {
        expandedIndices[index] = !expandedIndices[index];
    }

    function applyFilter() {
        router.get('/dashboard/logs', {
            q: searchQuery || undefined,
            level: selectedLevel || undefined
        }, {
            preserveState: true,
            replace: true
        });
    }

    function resetFilter() {
        searchQuery = '';
        selectedLevel = '';
        router.get('/dashboard/logs');
    }

    function getBadgeClass(lvl) {
        switch (lvl?.toLowerCase()) {
            case 'emergency':
            case 'alert':
            case 'critical':
            case 'error':
                return 'badge-error';
            case 'warning':
                return 'badge-warning';
            case 'notice':
            case 'info':
                return 'badge-info';
            default:
                return 'badge-neutral';
        }
    }
</script>

<DashboardLayout title="System Logs">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-base-content">Application Logs</h2>
        <p class="text-xs text-base-content/50 mt-0.5">Live monitoring of system logs and error stack traces</p>
    </div>

    <!-- Filter Card -->
    <div class="card bg-base-100 border border-base-300 shadow-sm p-4 mb-6 rounded-2xl">
        <form on:submit|preventDefault={applyFilter} class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full relative">
                <input
                    type="text"
                    bind:value={searchQuery}
                    placeholder="Search logs by keyword..."
                    class="input input-bordered input-sm w-full rounded-xl bg-base-100 pl-9"
                />
                <svg class="w-4 h-4 text-base-content/40 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <select
                bind:value={selectedLevel}
                on:change={applyFilter}
                class="select select-bordered select-sm rounded-xl w-full sm:w-44 bg-base-100 text-xs font-medium"
            >
                <option value="">All Log Levels</option>
                {#each levels as lvl}
                    <option value={lvl}>{lvl.toUpperCase()}</option>
                {/each}
            </select>

            <button type="submit" class="btn btn-neutral btn-sm rounded-xl px-4 w-full sm:w-auto">
                Filter
            </button>

            {#if searchQuery || selectedLevel}
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

    <!-- Logs Container -->
    <div bind:this={container} class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden rounded-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-base-200 bg-base-200/30">
            <p class="text-xs font-semibold text-base-content/60 uppercase tracking-wider">
                {entries.total || entries.data?.length || 0} Entries Found
            </p>
        </div>

        {#if !entries.data || entries.data.length === 0}
            <div class="px-6 py-16 text-center text-base-content/50 text-sm">
                No log entries matched your filter.
            </div>
        {:else}
            <div class="divide-y divide-base-200 font-mono">
                {#each entries.data as entry, i}
                    {@const lines = (entry.message || '').split('\n')}
                    {@const firstLine = lines[0]}
                    {@const hasMore = lines.length > 1}
                    {@const isExpanded = !!expandedIndices[i]}

                    <div class="p-4 hover:bg-base-200/30 transition-colors">
                        {#if hasMore}
                            <button
                                type="button"
                                class="w-full text-left flex items-start gap-3.5 cursor-pointer select-none bg-transparent border-0 p-0 m-0"
                                on:click={() => toggleExpand(i)}
                            >
                                <span class="badge {getBadgeClass(entry.level)} badge-soft badge-xs font-mono font-bold px-2 py-1 shrink-0 uppercase">
                                    {entry.level}
                                </span>

                                <div class="min-w-0 flex-1 font-sans">
                                    <p class="text-[11px] text-base-content/40 mb-1 font-mono">{entry.timestamp}</p>
                                    <p class="text-xs text-base-content font-mono break-all leading-relaxed">{firstLine}</p>
                                </div>

                                <svg class="w-4 h-4 text-base-content/40 shrink-0 transition-transform duration-200 mt-1 {isExpanded ? 'rotate-90' : ''}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </button>
                        {:else}
                            <div class="flex items-start gap-3.5">
                                <span class="badge {getBadgeClass(entry.level)} badge-soft badge-xs font-mono font-bold px-2 py-1 shrink-0 uppercase">
                                    {entry.level}
                                </span>

                                <div class="min-w-0 flex-1 font-sans">
                                    <p class="text-[11px] text-base-content/40 mb-1 font-mono">{entry.timestamp}</p>
                                    <p class="text-xs text-base-content font-mono break-all leading-relaxed">{firstLine}</p>
                                </div>
                            </div>
                        {/if}

                        {#if hasMore && isExpanded}
                            <div class="mt-3 ml-12">
                                <pre class="text-xs text-base-content/80 bg-base-200 border border-base-300 rounded-xl p-4 overflow-x-auto whitespace-pre-wrap font-mono leading-relaxed">{entry.message}</pre>
                            </div>
                        {/if}
                    </div>
                {/each}
            </div>

            <!-- Pagination -->
            {#if entries.links && entries.links.length > 3}
                <div class="p-4 border-t border-base-200 flex items-center justify-between">
                    <p class="text-xs text-base-content/50 font-sans">
                        Showing {entries.data.length} of {entries.total} logs
                    </p>
                    <div class="join">
                        {#each entries.links as link}
                            {#if link.url}
                                <Link
                                    href={link.url}
                                    class="join-item btn btn-xs font-sans {link.active ? 'btn-neutral' : 'btn-ghost'}"
                                >
                                    {@html link.label}
                                </Link>
                            {:else}
                                <span class="join-item btn btn-xs btn-disabled font-sans opacity-40">
                                    {@html link.label}
                                </span>
                            {/if}
                        {/each}
                    </div>
                </div>
            {/if}
        {/if}
    </div>
</DashboardLayout>
