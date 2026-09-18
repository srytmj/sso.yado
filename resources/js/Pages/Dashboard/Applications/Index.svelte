<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, router } from '@inertiajs/svelte';
    import DashboardLayout from '../../../Layouts/DashboardLayout.svelte';

    export let clients = [];

    let tableContainer;
    let deletingId = null;
    let deleteDialog;
    let pendingDelete = null;

    onMount(() => {
        if (tableContainer) {
            gsap.from(tableContainer, {
                opacity: 0,
                y: 15,
                duration: 0.5,
                ease: 'power2.out'
            });
        }
    });

    function confirmDelete(client) {
        pendingDelete = client;
        deleteDialog?.showModal();
    }

    function closeDeleteModal() {
        deleteDialog?.close();
        pendingDelete = null;
    }

    function performDelete() {
        if (!pendingDelete) return;
        const client = pendingDelete;
        deletingId = client.id;
        deleteDialog?.close();

        router.delete(`/dashboard/applications/${client.id}`, {
            preserveScroll: true,
            onFinish: () => {
                deletingId = null;
                pendingDelete = null;
            }
        });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('en-GB', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }
</script>

<DashboardLayout title="Applications">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-base-content">Connected Applications</h2>
            <p class="text-xs text-base-content/50 mt-0.5">Manage OAuth2 clients configured on this SSO engine</p>
        </div>

        <Link href="/dashboard/applications/create" class="btn btn-neutral btn-sm gap-2 rounded-xl">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Register Application
        </Link>
    </div>

    <div bind:this={tableContainer} class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead class="bg-base-200/50 text-base-content/60 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-6">Name</th>
                        <th class="py-3 px-4 hidden sm:table-cell">Client ID</th>
                        <th class="py-3 px-4 hidden lg:table-cell">Redirect URI</th>
                        <th class="py-3 px-4 hidden md:table-cell">Created</th>
                        <th class="py-3 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    {#if !clients || clients.length === 0}
                        <tr>
                            <td colspan="5" class="text-center py-16 text-base-content/50 text-sm">
                                <div class="max-w-sm mx-auto">
                                    <p class="font-medium text-base-content mb-1">No applications found</p>
                                    <p class="text-xs mb-4">You haven't registered any client applications yet.</p>
                                    <Link href="/dashboard/applications/create" class="btn btn-neutral btn-xs rounded-lg">
                                        Register your first app
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    {:else}
                        {#each clients as client}
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <td class="py-4 px-6 font-medium text-base-content">
                                    <Link href="/dashboard/applications/{client.id}" class="hover:underline">
                                        {client.name}
                                    </Link>
                                </td>
                                <td class="py-4 px-4 font-mono text-xs text-base-content/60 hidden sm:table-cell">
                                    {client.id}
                                </td>
                                <td class="py-4 px-4 font-mono text-xs text-base-content/60 max-w-xs truncate hidden lg:table-cell" title={client.redirect}>
                                    {client.redirect || '-'}
                                </td>
                                <td class="py-4 px-4 text-xs text-base-content/60 hidden md:table-cell whitespace-nowrap">
                                    {formatDate(client.created_at)}
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link href="/dashboard/applications/{client.id}" class="btn btn-outline btn-xs rounded-lg">
                                            Detail
                                        </Link>
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs text-error hover:bg-error/10 rounded-lg"
                                            disabled={deletingId === client.id}
                                            on:click={() => confirmDelete(client)}
                                        >
                                            {#if deletingId === client.id}
                                                <span class="loading loading-spinner loading-xs"></span>
                                            {:else}
                                                Delete
                                            {/if}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    {/if}
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <dialog bind:this={deleteDialog} class="modal" on:close={() => pendingDelete = null}>
        <div class="modal-box max-w-sm rounded-2xl">
            <h4 class="text-sm font-bold text-base-content mb-2">Delete Application</h4>
            {#if pendingDelete}
                <p class="text-sm text-base-content/70">
                    Are you sure you want to delete <span class="font-semibold text-base-content">"{pendingDelete.name}"</span>?
                    All associated tokens will be revoked immediately. This action cannot be undone.
                </p>
            {/if}

            <div class="pt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-ghost btn-sm rounded-xl" on:click={closeDeleteModal}>
                    Cancel
                </button>
                <button type="button" class="btn btn-error btn-sm rounded-xl px-4" on:click={performDelete}>
                    Delete
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button on:click={closeDeleteModal}>close</button>
        </form>
    </dialog>
</DashboardLayout>
