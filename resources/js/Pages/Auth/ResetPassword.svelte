<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, useForm } from '@inertiajs/svelte';
    import AuthLayout from '../../Layouts/AuthLayout.svelte';

    export let token = '';
    export let email = '';

    let formElements;
    let title;

    const form = useForm({
        token: token || '',
        email: email || '',
        password: '',
        password_confirmation: ''
    });

    onMount(() => {
        gsap.from(title, { opacity: 0, x: -20, duration: 0.6, delay: 0.3, ease: 'power3.out' });
        gsap.from(formElements.children, {
            opacity: 0,
            y: 15,
            duration: 0.5,
            stagger: 0.08,
            delay: 0.4,
            ease: 'power2.out'
        });
    });

    function submit() {
        $form.post('/reset-password');
    }
</script>

<svelte:head>
    <title>Set New Password - SSO Yado</title>
</svelte:head>

<AuthLayout>
    <h2 bind:this={title} class="text-2xl font-semibold mb-2 text-base-content">New Password</h2>
    <p class="text-xs text-base-content/60 mb-6">Choose a strong password to secure your account.</p>

    <form on:submit|preventDefault={submit} bind:this={formElements} class="space-y-4">
        {#if $form.errors.email}
            <div class="p-3 rounded-xl bg-error/10 text-error text-xs border border-error/20">
                {$form.errors.email}
            </div>
        {/if}

        <div class="form-control">
            <label class="label" for="rp-email"><span class="label-text font-medium">Email Address</span></label>
            <input
                id="rp-email"
                type="email"
                value={form.email}
                disabled
                class="input input-bordered w-full bg-base-200 text-base-content/60 cursor-not-allowed rounded-xl"
            />
        </div>

        <div class="form-control">
            <label class="label" for="rp-pass"><span class="label-text font-medium">New Password</span></label>
            <input
                id="rp-pass"
                type="password"
                bind:value={$form.password}
                placeholder="Min. 8 characters"
                required
                class="input input-bordered w-full bg-base-100 rounded-xl focus:border-base-content"
            />
            {#if $form.errors.password}
                <p class="text-xs text-error mt-1">{$form.errors.password}</p>
            {/if}
        </div>

        <div class="form-control">
            <label class="label" for="rp-pass-confirm"><span class="label-text font-medium">Confirm New Password</span></label>
            <input
                id="rp-pass-confirm"
                type="password"
                bind:value={$form.password_confirmation}
                placeholder="Repeat password"
                required
                class="input input-bordered w-full bg-base-100 rounded-xl focus:border-base-content"
            />
        </div>

        <button type="submit" class="btn btn-neutral w-full mt-4 rounded-xl shadow-lg shadow-neutral/20" disabled={$form.processing}>
            {#if $form.processing}
                <span class="loading loading-spinner loading-sm"></span>
            {:else}
                Save New Password
            {/if}
        </button>
    </form>
</AuthLayout>
