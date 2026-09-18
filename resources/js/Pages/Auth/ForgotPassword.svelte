<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, useForm, page } from '@inertiajs/svelte';
    import AuthLayout from '../../Layouts/AuthLayout.svelte';

    let formElements;
    let title;

    $: status = $page.props.flash?.status || null;

    const form = useForm({
        email: ''
    });

    onMount(() => {
        gsap.from(title, { opacity: 0, x: -20, duration: 0.6, delay: 0.3, ease: 'power3.out' });
        gsap.from(formElements.children, {
            opacity: 0,
            y: 15,
            duration: 0.5,
            stagger: 0.1,
            delay: 0.4,
            ease: 'power2.out'
        });
    });

    function submit() {
        $form.post('/forgot-password');
    }
</script>

<svelte:head>
    <title>Forgot Password - SSO Yado</title>
</svelte:head>

<AuthLayout>
    <h2 bind:this={title} class="text-2xl font-semibold mb-2 text-base-content">Reset Password</h2>
    <p class="text-xs text-base-content/60 mb-6">Enter your email and we will send a password reset link.</p>

    {#if status}
        <div class="p-3 mb-4 rounded-xl bg-success/10 text-success text-xs border border-success/20">
            {status}
        </div>
    {/if}

    <form on:submit|preventDefault={submit} bind:this={formElements} class="space-y-4">
        {#if $form.errors.email}
            <div class="p-3 rounded-xl bg-error/10 text-error text-xs border border-error/20">
                {$form.errors.email}
            </div>
        {/if}

        <div class="form-control">
            <label class="label" for="fp-email"><span class="label-text font-medium">Email Address</span></label>
            <input
                id="fp-email"
                type="email"
                bind:value={$form.email}
                required
                class="input input-bordered w-full bg-base-100 transition-colors focus:border-base-content rounded-xl"
            />
        </div>

        <button type="submit" class="btn btn-neutral w-full mt-4 rounded-xl shadow-lg shadow-neutral/20" disabled={$form.processing}>
            {#if $form.processing}
                <span class="loading loading-spinner loading-sm"></span>
            {:else}
                Send Reset Link
            {/if}
        </button>

        <div class="text-center mt-6 text-sm text-base-content/60">
            Remember your password?
            <Link href="/login" class="font-semibold text-base-content hover:underline transition-colors">Sign In</Link>
        </div>
    </form>
</AuthLayout>
