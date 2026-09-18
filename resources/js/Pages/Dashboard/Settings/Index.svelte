<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { useForm } from '@inertiajs/svelte';
    import DashboardLayout from '../../../Layouts/DashboardLayout.svelte';

    export let settings = {};

    let container;

    // Mail Form
    const mailForm = useForm({
        mail_driver: settings.mail_driver || 'resend',
        mail_from_address: settings.mail_from_address || '',
        mail_from_name: settings.mail_from_name || '',
        resend_api_key: settings.resend_api_key || '',
        smtp_host: settings.smtp_host || '',
        smtp_port: settings.smtp_port || 587,
        smtp_username: settings.smtp_username || '',
        smtp_password: '',
        smtp_encryption: settings.smtp_encryption || 'tls'
    });

    // Test Email Form
    const testEmailForm = useForm({
        test_email: ''
    });

    // Avatar Storage Form
    const storageForm = useForm({
        avatar_disk: settings.avatar_disk || 'local',
        s3_key: settings.s3_key || '',
        s3_secret: '',
        s3_region: settings.s3_region || '',
        s3_bucket: settings.s3_bucket || '',
        s3_endpoint: settings.s3_endpoint || ''
    });

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

    function saveMail() {
        $mailForm.post('/dashboard/settings/mail');
    }

    function sendTest() {
        $testEmailForm.post('/dashboard/settings/test-email');
    }

    function saveStorage() {
        $storageForm.post('/dashboard/settings/avatar-storage');
    }
</script>

<DashboardLayout title="Settings">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-base-content">System Settings</h2>
            <p class="text-xs text-base-content/50 mt-0.5">Configure transactional mailing and avatar storage adapters</p>
        </div>

        <div bind:this={container} class="space-y-8">
            <!-- Mail Server Configuration -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                <div class="card-body p-6 sm:p-8">
                    <div class="mb-5 pb-3 border-b border-base-200">
                        <h3 class="text-base font-semibold text-base-content">Email Configuration</h3>
                        <p class="text-xs text-base-content/50 mt-0.5">Setup Resend or SMTP for sending authentication codes and user invitations</p>
                    </div>

                    <form on:submit|preventDefault={saveMail} class="space-y-6">
                        <!-- Driver Selector -->
                        <div class="form-control">
                            <div class="label"><span class="label-text font-medium">Mailing Driver</span></div>
                            <div class="grid grid-cols-2 gap-3 max-w-sm">
                                <label class="flex items-center gap-3 p-3 border border-base-300 rounded-xl cursor-pointer hover:bg-base-200/50 {$mailForm.mail_driver === 'resend' ? 'border-neutral bg-neutral/5 font-semibold' : ''}">
                                    <input
                                        type="radio"
                                        bind:group={$mailForm.mail_driver}
                                        value="resend"
                                        class="radio radio-sm radio-neutral"
                                    />
                                    <span class="text-sm">Resend API</span>
                                </label>

                                <label class="flex items-center gap-3 p-3 border border-base-300 rounded-xl cursor-pointer hover:bg-base-200/50 {$mailForm.mail_driver === 'smtp' ? 'border-neutral bg-neutral/5 font-semibold' : ''}">
                                    <input
                                        type="radio"
                                        bind:group={$mailForm.mail_driver}
                                        value="smtp"
                                        class="radio radio-sm radio-neutral"
                                    />
                                    <span class="text-sm">Standard SMTP</span>
                                </label>
                            </div>
                        </div>

                        <!-- From Address & Name -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label" for="from-email"><span class="label-text font-medium">From Email</span></label>
                                <input
                                    id="from-email"
                                    type="email"
                                    bind:value={$mailForm.mail_from_address}
                                    placeholder="noreply@yado.my.id"
                                    required
                                    class="input input-bordered w-full rounded-xl bg-base-100"
                                />
                                {#if $mailForm.errors.mail_from_address}
                                    <p class="text-xs text-error mt-1">{$mailForm.errors.mail_from_address}</p>
                                {/if}
                            </div>

                            <div class="form-control">
                                <label class="label" for="from-name"><span class="label-text font-medium">From Name</span></label>
                                <input
                                    id="from-name"
                                    type="text"
                                    bind:value={$mailForm.mail_from_name}
                                    placeholder="Yado SSO"
                                    required
                                    class="input input-bordered w-full rounded-xl bg-base-100"
                                />
                                {#if $mailForm.errors.mail_from_name}
                                    <p class="text-xs text-error mt-1">{$mailForm.errors.mail_from_name}</p>
                                {/if}
                            </div>
                        </div>

                        <!-- Resend Settings -->
                        {#if $mailForm.mail_driver === 'resend'}
                            <div class="form-control p-4 bg-base-200/40 rounded-xl border border-base-300">
                                <label class="label" for="resend-key"><span class="label-text font-medium">Resend API Key</span></label>
                                <input
                                    id="resend-key"
                                    type="password"
                                    bind:value={$mailForm.resend_api_key}
                                    placeholder="re_xxxxxxxxxxxx"
                                    class="input input-bordered w-full rounded-xl bg-base-100 font-mono text-sm"
                                />
                                {#if $mailForm.errors.resend_api_key}
                                    <p class="text-xs text-error mt-1">{$mailForm.errors.resend_api_key}</p>
                                {/if}
                            </div>
                        {:else}
                            <!-- SMTP Settings -->
                            <div class="space-y-4 p-4 bg-base-200/40 rounded-xl border border-base-300">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="form-control">
                                        <label class="label" for="smtp-host"><span class="label-text font-medium">SMTP Host</span></label>
                                        <input id="smtp-host" type="text" bind:value={$mailForm.smtp_host} placeholder="smtp.mailgun.org" class="input input-bordered w-full rounded-xl bg-base-100" />
                                    </div>
                                    <div class="form-control">
                                        <label class="label" for="smtp-port"><span class="label-text font-medium">SMTP Port</span></label>
                                        <input id="smtp-port" type="number" bind:value={$mailForm.smtp_port} placeholder="587" class="input input-bordered w-full rounded-xl bg-base-100" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="form-control">
                                        <label class="label" for="smtp-user"><span class="label-text font-medium">SMTP Username</span></label>
                                        <input id="smtp-user" type="text" bind:value={$mailForm.smtp_username} class="input input-bordered w-full rounded-xl bg-base-100" />
                                    </div>
                                    <div class="form-control">
                                        <label class="label" for="smtp-pass"><span class="label-text font-medium">SMTP Password</span></label>
                                        <input id="smtp-pass" type="password" bind:value={$mailForm.smtp_password} placeholder="Leave blank to keep unchanged" class="input input-bordered w-full rounded-xl bg-base-100" />
                                    </div>
                                </div>

                                <div class="form-control max-w-xs">
                                    <label class="label" for="smtp-enc"><span class="label-text font-medium">Encryption</span></label>
                                    <select id="smtp-enc" bind:value={$mailForm.smtp_encryption} class="select select-bordered w-full rounded-xl bg-base-100">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                    </select>
                                </div>
                            </div>
                        {/if}

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="btn btn-neutral btn-sm rounded-xl px-5"
                                disabled={$mailForm.processing}
                            >
                                {#if $mailForm.processing}
                                    <span class="loading loading-spinner loading-xs"></span>
                                {:else}
                                    Save Mail Settings
                                {/if}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test Email Card -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                <div class="card-body p-6 sm:p-8">
                    <h3 class="text-base font-semibold text-base-content mb-1">Test Mail Delivery</h3>
                    <p class="text-xs text-base-content/50 mb-4">Send an immediate test message to verify your mailing setup.</p>

                    <form on:submit|preventDefault={sendTest} class="flex flex-col sm:flex-row items-center gap-3">
                        <input
                            type="email"
                            bind:value={$testEmailForm.test_email}
                            placeholder="your-email@example.com"
                            required
                            class="input input-bordered w-full rounded-xl bg-base-100"
                        />
                        <button
                            type="submit"
                            class="btn btn-outline btn-sm rounded-xl px-5 shrink-0 w-full sm:w-auto"
                            disabled={$testEmailForm.processing}
                        >
                            {#if $testEmailForm.processing}
                                <span class="loading loading-spinner loading-xs"></span>
                            {:else}
                                Send Test Email
                            {/if}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Avatar Storage Configuration -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                <div class="card-body p-6 sm:p-8">
                    <div class="mb-5 pb-3 border-b border-base-200">
                        <h3 class="text-base font-semibold text-base-content">Avatar Storage</h3>
                        <p class="text-xs text-base-content/50 mt-0.5">Choose where user profile pictures and assets are persisted</p>
                    </div>

                    <form on:submit|preventDefault={saveStorage} class="space-y-6">
                        <div class="form-control">
                            <div class="label"><span class="label-text font-medium">Storage Adapter</span></div>
                            <div class="grid grid-cols-2 gap-3 max-w-sm">
                                <label class="flex items-center gap-3 p-3 border border-base-300 rounded-xl cursor-pointer hover:bg-base-200/50 {$storageForm.avatar_disk === 'local' ? 'border-neutral bg-neutral/5 font-semibold' : ''}">
                                    <input
                                        type="radio"
                                        bind:group={$storageForm.avatar_disk}
                                        value="local"
                                        class="radio radio-sm radio-neutral"
                                    />
                                    <span class="text-sm">Local Filesystem</span>
                                </label>

                                <label class="flex items-center gap-3 p-3 border border-base-300 rounded-xl cursor-pointer hover:bg-base-200/50 {$storageForm.avatar_disk === 's3' ? 'border-neutral bg-neutral/5 font-semibold' : ''}">
                                    <input
                                        type="radio"
                                        bind:group={$storageForm.avatar_disk}
                                        value="s3"
                                        class="radio radio-sm radio-neutral"
                                    />
                                    <span class="text-sm">Amazon S3 / R2</span>
                                </label>
                            </div>
                        </div>

                        {#if $storageForm.avatar_disk === 's3'}
                            <div class="space-y-4 p-4 bg-base-200/40 rounded-xl border border-base-300">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="form-control">
                                        <label class="label" for="s3-key"><span class="label-text font-medium">S3 Key</span></label>
                                        <input id="s3-key" type="text" bind:value={$storageForm.s3_key} class="input input-bordered w-full rounded-xl bg-base-100 font-mono text-sm" />
                                    </div>
                                    <div class="form-control">
                                        <label class="label" for="s3-secret"><span class="label-text font-medium">S3 Secret</span></label>
                                        <input id="s3-secret" type="password" bind:value={$storageForm.s3_secret} placeholder="Leave blank to keep unchanged" class="input input-bordered w-full rounded-xl bg-base-100 font-mono text-sm" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="form-control">
                                        <label class="label" for="s3-region"><span class="label-text font-medium">Region</span></label>
                                        <input id="s3-region" type="text" bind:value={$storageForm.s3_region} placeholder="us-east-1" class="input input-bordered w-full rounded-xl bg-base-100" />
                                    </div>
                                    <div class="form-control">
                                        <label class="label" for="s3-bucket"><span class="label-text font-medium">Bucket Name</span></label>
                                        <input id="s3-bucket" type="text" bind:value={$storageForm.s3_bucket} placeholder="yado-avatars" class="input input-bordered w-full rounded-xl bg-base-100" />
                                    </div>
                                </div>

                                <div class="form-control">
                                    <label class="label" for="s3-endpoint"><span class="label-text font-medium">Custom S3 Endpoint (Optional for Cloudflare R2 / MinIO)</span></label>
                                    <input id="s3-endpoint" type="text" bind:value={$storageForm.s3_endpoint} placeholder="https://<account-id>.r2.cloudflarestorage.com" class="input input-bordered w-full rounded-xl bg-base-100 font-mono text-sm" />
                                </div>
                            </div>
                        {/if}

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="btn btn-neutral btn-sm rounded-xl px-5"
                                disabled={$storageForm.processing}
                            >
                                {#if $storageForm.processing}
                                    <span class="loading loading-spinner loading-xs"></span>
                                {:else}
                                    Save Storage Settings
                                {/if}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</DashboardLayout>
