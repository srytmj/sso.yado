<script>
    import { useForm } from '@inertiajs/svelte';
    import AccountLayout from '../../Layouts/AccountLayout.svelte';

    export let user = {};

    const passwordForm = useForm({
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
    });

    const avatarForm = useForm({
        avatar: null
    });

    function submitPassword() {
        $passwordForm.post('/account/password', {
            onSuccess: () => $passwordForm.reset()
        });
    }

    function submitAvatar(e) {
        const file = e.target.files[0];
        // Reset immediately so a stray double-fire of `change` (or re-selecting
        // the same file later) can't queue a second request for one pick.
        e.target.value = '';

        if (!file || $avatarForm.processing) return;

        $avatarForm.avatar = file;
        $avatarForm.post('/account/avatar', {
            preserveScroll: true,
            onFinish: () => $avatarForm.reset('avatar')
        });
    }
</script>

<AccountLayout title="Profile & Password">
    <div class="space-y-6">
        <!-- Profile Card -->
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
            <div class="card-body p-6 sm:p-8">
                <div class="mb-5 pb-3 border-b border-base-200">
                    <h3 class="text-base font-semibold text-base-content">Profile Details</h3>
                    <p class="text-xs text-base-content/50 mt-0.5">Your personal information and avatar</p>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 mb-6">
                    <!-- Avatar -->
                    <div class="relative group">
                        <div class="w-20 h-20 rounded-full bg-neutral text-neutral-content flex items-center justify-center font-bold text-2xl overflow-hidden border-2 border-base-300">
                            {#if user.avatar_url}
                                <img src={user.avatar_url} alt={user.name} class="w-full h-full object-cover" />
                            {:else}
                                {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                            {/if}
                        </div>
                        <label class="btn btn-circle btn-neutral btn-xs absolute bottom-0 right-0 cursor-pointer shadow">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                            </svg>
                            <input type="file" accept="image/*" class="hidden" on:change={submitAvatar} />
                        </label>
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h4 class="text-lg font-bold text-base-content">{user.name}</h4>
                            <span class="badge badge-neutral badge-xs font-mono uppercase">{user.role?.name || 'Member'}</span>
                        </div>
                        <p class="text-xs text-base-content/60 font-mono">@{user.username || 'user'} · {user.email}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-3 bg-base-200/50 rounded-xl">
                        <span class="text-base-content/40 block mb-0.5">Email Status</span>
                        <span class="font-medium text-success">Verified</span>
                    </div>
                    <div class="p-3 bg-base-200/50 rounded-xl">
                        <span class="text-base-content/40 block mb-0.5">Account ID</span>
                        <span class="font-mono text-base-content/70">{user.account_id || user.id}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
            <div class="card-body p-6 sm:p-8">
                <div class="mb-5 pb-3 border-b border-base-200">
                    <h3 class="text-base font-semibold text-base-content">Change Password</h3>
                    <p class="text-xs text-base-content/50 mt-0.5">Update your password to keep your account safe</p>
                </div>

                <form on:submit|preventDefault={submitPassword} class="space-y-4 max-w-md">
                    {#if $passwordForm.hasErrors}
                        <div class="p-3 bg-error/10 border border-error/20 rounded-xl text-error text-xs">
                            Please correct the errors below.
                        </div>
                    {/if}

                    <div class="form-control">
                        <label class="label" for="cur-pass"><span class="label-text font-medium">Current Password</span></label>
                        <input
                            id="cur-pass"
                            type="password"
                            bind:value={$passwordForm.current_password}
                            required
                            class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                        />
                        {#if $passwordForm.errors.current_password}
                            <p class="text-xs text-error mt-1">{$passwordForm.errors.current_password}</p>
                        {/if}
                    </div>

                    <div class="form-control">
                        <label class="label" for="acc-new-pass"><span class="label-text font-medium">New Password</span></label>
                        <input
                            id="acc-new-pass"
                            type="password"
                            bind:value={$passwordForm.new_password}
                            required
                            placeholder="Min 8 characters"
                            class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                        />
                        {#if $passwordForm.errors.new_password}
                            <p class="text-xs text-error mt-1">{$passwordForm.errors.new_password}</p>
                        {/if}
                    </div>

                    <div class="form-control">
                        <label class="label" for="acc-new-pass-confirm"><span class="label-text font-medium">Confirm New Password</span></label>
                        <input
                            id="acc-new-pass-confirm"
                            type="password"
                            bind:value={$passwordForm.new_password_confirmation}
                            required
                            placeholder="Repeat new password"
                            class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                        />
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="btn btn-neutral btn-sm rounded-xl px-5"
                            disabled={$passwordForm.processing}
                        >
                            {#if $passwordForm.processing}
                                <span class="loading loading-spinner loading-xs"></span>
                            {:else}
                                Update Password
                            {/if}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</AccountLayout>
