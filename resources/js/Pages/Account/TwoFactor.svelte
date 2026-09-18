<script>
    import { useForm } from '@inertiajs/svelte';
    import AccountLayout from '../../Layouts/AccountLayout.svelte';

    export let user = {};
    export let qrCodeSvg = null;
    export let manualKey = null;
    export let recoveryCodes = null;

    $: isEnabled = !!user?.two_factor_secret;

    const enableForm = useForm({
        current_password: ''
    });

    const confirmForm = useForm({
        code: ''
    });

    const disableForm = useForm({
        current_password: ''
    });

    function startEnable() {
        $enableForm.post('/account/two-factor/enable');
    }

    function confirmCode() {
        $confirmForm.post('/account/two-factor/confirm');
    }

    function disableTwoFactor() {
        if (confirm('Are you sure you want to disable two-factor authentication?')) {
            $disableForm.delete('/account/two-factor');
        }
    }
</script>

<AccountLayout title="Two-Factor Security">
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
        <div class="card-body p-6 sm:p-8">
            <div class="flex items-start justify-between mb-5 pb-3 border-b border-base-200">
                <div>
                    <h3 class="text-base font-semibold text-base-content">Two-Factor Authentication</h3>
                    <p class="text-xs text-base-content/50 mt-0.5">Protect your account using time-based one-time passwords (TOTP)</p>
                </div>
                <span class="badge {isEnabled ? 'badge-success' : 'badge-neutral'} badge-soft badge-sm font-medium">
                    {isEnabled ? 'Enabled' : 'Disabled'}
                </span>
            </div>

            <!-- Recovery Codes Banner (shown once after enabling) -->
            {#if recoveryCodes && recoveryCodes.length > 0}
                <div class="p-5 bg-warning/10 border border-warning/30 rounded-2xl mb-6">
                    <p class="text-xs font-bold text-warning mb-1">Save your recovery codes!</p>
                    <p class="text-xs text-base-content/70 mb-3">
                        These emergency recovery codes can be used to log in if you lose access to your authenticator device. Each code can only be used once.
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-xs">
                        {#each recoveryCodes as code}
                            <code class="p-2 bg-base-100 border border-warning/20 rounded-lg text-center font-bold text-base-content">{code}</code>
                        {/each}
                    </div>
                </div>
            {/if}

            {#if qrCodeSvg}
                <!-- Step 2: Scan QR code and enter verification code -->
                <div class="max-w-md space-y-4">
                    <div>
                        <h4 class="text-sm font-bold text-base-content mb-1">Scan QR Code</h4>
                        <p class="text-xs text-base-content/60 mb-3">Scan this barcode using Google Authenticator, Authy, or 1Password.</p>

                        <div class="p-4 bg-white rounded-2xl border border-base-300 inline-block">
                            {@html qrCodeSvg}
                        </div>
                    </div>

                    {#if manualKey}
                        <div>
                            <p class="text-xs text-base-content/50 mb-1">Or enter key manually:</p>
                            <code class="text-xs font-mono bg-base-200 px-3 py-2 rounded-xl text-base-content block break-all font-bold">{manualKey}</code>
                        </div>
                    {/if}

                    <form on:submit|preventDefault={confirmCode} class="space-y-4 pt-2">
                        <div class="form-control">
                            <label class="label" for="conf-code"><span class="label-text font-medium">Enter 6-digit Code</span></label>
                            <input
                                id="conf-code"
                                type="text"
                                bind:value={$confirmForm.code}
                                inputmode="numeric"
                                placeholder="123456"
                                required
                                class="input input-bordered w-full rounded-xl bg-base-100 font-mono text-center tracking-widest text-lg focus:border-base-content"
                            />
                            {#if $confirmForm.errors.code}
                                <p class="text-xs text-error mt-1">{$confirmForm.errors.code}</p>
                            {/if}
                        </div>

                        <button
                            type="submit"
                            class="btn btn-neutral btn-sm rounded-xl px-5"
                            disabled={$confirmForm.processing}
                        >
                            Confirm & Enable
                        </button>
                    </form>
                </div>
            {:else if isEnabled}
                <!-- Two-Factor is currently Enabled: provide option to disable -->
                <div class="max-w-md space-y-4">
                    <p class="text-xs text-base-content/70">
                        Two-factor authentication is active on your account. Every sign-in requires your password and a code from your authenticator app.
                    </p>

                    <form on:submit|preventDefault={disableTwoFactor} class="space-y-3 pt-2">
                        <div class="form-control">
                            <label class="label" for="dis-pass"><span class="label-text font-medium">Current Password</span></label>
                            <input
                                id="dis-pass"
                                type="password"
                                bind:value={$disableForm.current_password}
                                placeholder="Verify password to disable"
                                required
                                class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                            />
                            {#if $disableForm.errors.current_password}
                                <p class="text-xs text-error mt-1">{$disableForm.errors.current_password}</p>
                            {/if}
                        </div>

                        <button
                            type="submit"
                            class="btn btn-error btn-sm rounded-xl px-5"
                            disabled={$disableForm.processing}
                        >
                            Disable Two-Factor
                        </button>
                    </form>
                </div>
            {:else}
                <!-- Two-factor is Disabled: start setup -->
                <div class="max-w-md space-y-4">
                    <p class="text-xs text-base-content/70">
                        Add an extra layer of security to your account. When enabled, you'll need to enter both your password and an authentication code from your mobile device.
                    </p>

                    <form on:submit|preventDefault={startEnable} class="space-y-3 pt-2">
                        <div class="form-control">
                            <label class="label" for="en-pass"><span class="label-text font-medium">Current Password</span></label>
                            <input
                                id="en-pass"
                                type="password"
                                bind:value={$enableForm.current_password}
                                placeholder="Verify password to continue"
                                required
                                class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                            />
                            {#if $enableForm.errors.current_password}
                                <p class="text-xs text-error mt-1">{$enableForm.errors.current_password}</p>
                            {/if}
                        </div>

                        <button
                            type="submit"
                            class="btn btn-neutral btn-sm rounded-xl px-5"
                            disabled={$enableForm.processing}
                        >
                            Enable Two-Factor
                        </button>
                    </form>
                </div>
            {/if}
        </div>
    </div>
</AccountLayout>
