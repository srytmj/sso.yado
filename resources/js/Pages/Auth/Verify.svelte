<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, useForm, page } from '@inertiajs/svelte';
    import AuthLayout from '../../Layouts/AuthLayout.svelte';

    let title;
    let container;

    $: status = $page.props.flash?.status || null;

    const form = useForm({});

    onMount(() => {
        gsap.from(title, { opacity: 0, x: -20, duration: 0.6, delay: 0.3, ease: 'power3.out' });
        if (container) {
            gsap.from(container, { opacity: 0, y: 15, duration: 0.5, delay: 0.4, ease: 'power2.out' });
        }
    });

    function resend() {
        $form.post('/email/verification-notification');
    }
</script>

<svelte:head>
    <title>Verify Email - SSO Yado</title>
</svelte:head>

<AuthLayout>
    <h2 bind:this={title} class="text-2xl font-semibold mb-2 text-base-content">Verify Your Email</h2>
    <p class="text-xs text-base-content/60 mb-6">A verification link has been sent to your email address.</p>

    {#if status}
        <div class="p-3 mb-5 rounded-xl bg-success/10 text-success text-xs border border-success/20">
            A new verification link has been sent to your email address!
        </div>
    {/if}

    <div bind:this={container} class="space-y-4">
        <p class="text-sm text-base-content/70 text-center mb-6">
            Before proceeding, please check your email for a verification link. If you didn't receive the email, click the button below to request another.
        </p>

        <form on:submit|preventDefault={resend}>
            <button type="submit" class="btn btn-neutral w-full rounded-xl shadow-lg shadow-neutral/20" disabled={$form.processing}>
                {#if $form.processing}
                    <span class="loading loading-spinner loading-sm"></span>
                {:else}
                    Resend Verification Email
                {/if}
            </button>
        </form>

        <div class="divider my-4"></div>

        <div class="text-center">
            <Link href="/logout" method="post" as="button" class="btn btn-ghost btn-sm text-error hover:bg-error/10 rounded-xl">
                Sign Out
            </Link>
        </div>
    </div>
</AuthLayout>
