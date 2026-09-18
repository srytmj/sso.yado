<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, useForm, page } from '@inertiajs/svelte';
    import AuthLayout from '../../Layouts/AuthLayout.svelte';

    export let invitation = null;
    export let error = null;

    let formElements;
    let title;

    const form = useForm({
        token: typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('token') || '') : '',
        name: '',
        username: '',
        password: '',
        password_confirmation: ''
    });

    onMount(() => {
        gsap.from(title, { opacity: 0, x: -20, duration: 0.6, delay: 0.3, ease: 'power3.out' });
        if (formElements) {
            gsap.from(formElements.children, {
                opacity: 0,
                y: 15,
                duration: 0.5,
                stagger: 0.08,
                delay: 0.4,
                ease: 'power2.out'
            });
        }
    });

    function submit() {
        $form.post('/register/invite');
    }
</script>

<svelte:head>
    <title>Accept Invitation - SSO Yado</title>
</svelte:head>

<AuthLayout>
    <h2 bind:this={title} class="text-2xl font-semibold mb-2 text-base-content">Accept Invitation</h2>
    <p class="text-xs text-base-content/60 mb-6">Complete your profile to join Yado SSO.</p>

    {#if error}
        <div class="p-4 bg-error/10 border border-error/20 rounded-2xl text-error text-xs mb-6">
            <p class="font-bold mb-1">Invalid or Expired Invitation</p>
            <p>{error}</p>
        </div>
        <div class="text-center">
            <Link href="/login" class="btn btn-neutral btn-sm rounded-xl">Go to Login</Link>
        </div>
    {:else if invitation}
        <div class="p-4 bg-base-200/60 border border-base-300 rounded-2xl text-xs mb-6 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-neutral text-neutral-content flex items-center justify-center font-bold text-xs shrink-0">
                {invitation.email ? invitation.email.charAt(0).toUpperCase() : 'U'}
            </div>
            <div class="min-w-0">
                <p class="font-medium text-base-content truncate">{invitation.email}</p>
                <p class="text-[11px] text-base-content/50">Invited with role: <span class="font-semibold text-base-content">{invitation.role?.name || 'Member'}</span></p>
            </div>
        </div>

        <form on:submit|preventDefault={submit} bind:this={formElements} class="space-y-4">
            <div class="form-control">
                <label class="label" for="inv-name"><span class="label-text font-medium">Full Name</span></label>
                <input
                    id="inv-name"
                    type="text"
                    bind:value={$form.name}
                    placeholder="Your name"
                    required
                    class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                />
                {#if $form.errors.name}
                    <p class="text-xs text-error mt-1">{$form.errors.name}</p>
                {/if}
            </div>

            <div class="form-control">
                <label class="label" for="inv-username"><span class="label-text font-medium">Username</span></label>
                <input
                    id="inv-username"
                    type="text"
                    bind:value={$form.username}
                    placeholder="unique_username"
                    required
                    class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                />
                {#if $form.errors.username}
                    <p class="text-xs text-error mt-1">{$form.errors.username}</p>
                {/if}
            </div>

            <div class="form-control">
                <label class="label" for="inv-pass"><span class="label-text font-medium">Create Password</span></label>
                <input
                    id="inv-pass"
                    type="password"
                    bind:value={$form.password}
                    placeholder="Min 8 characters"
                    required
                    class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                />
                {#if $form.errors.password}
                    <p class="text-xs text-error mt-1">{$form.errors.password}</p>
                {/if}
            </div>

            <div class="form-control">
                <label class="label" for="inv-pass-confirm"><span class="label-text font-medium">Confirm Password</span></label>
                <input
                    id="inv-pass-confirm"
                    type="password"
                    bind:value={$form.password_confirmation}
                    placeholder="Repeat password"
                    required
                    class="input input-bordered w-full rounded-xl bg-base-100 focus:border-base-content"
                />
            </div>

            <button type="submit" class="btn btn-neutral w-full mt-4 rounded-xl shadow-lg shadow-neutral/20" disabled={$form.processing}>
                {#if $form.processing}
                    <span class="loading loading-spinner loading-sm"></span>
                {:else}
                    Activate Account
                {/if}
            </button>
        </form>
    {/if}
</AuthLayout>
