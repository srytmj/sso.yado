<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { inertia, useForm } from '@inertiajs/svelte';
    import AuthLayout from '../../Layouts/AuthLayout.svelte';

    let formElements;
    let title;

    const form = useForm({
        email: '',
        password: '',
        remember: false
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
        $form.post('/login');
    }
</script>

<svelte:head>
    <title>Login - SSO Yado</title>
</svelte:head>

<AuthLayout>
    <h2 bind:this={title} class="text-2xl font-semibold mb-6 text-base-content">Sign In</h2>
    
    <form on:submit|preventDefault={submit} bind:this={formElements} class="space-y-5">
        
        {#if $form.errors.email}
            <div class="p-3 rounded bg-error/10 text-error text-sm border border-error/20">
                {$form.errors.email}
            </div>
        {/if}

        <div class="form-control">
            <label class="label"><span class="label-text font-medium">Email Address</span></label>
            <input type="email" bind:value={$form.email} required class="input input-bordered w-full bg-base-100 transition-colors focus:border-base-content" />
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-medium">Password</span></label>
            <input type="password" bind:value={$form.password} required class="input input-bordered w-full bg-base-100 transition-colors focus:border-base-content" />
        </div>

        <div class="flex items-center justify-between mt-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" bind:checked={$form.remember} class="checkbox checkbox-sm rounded" />
                <span class="label-text text-sm">Remember me</span>
            </label>
            <a href="/forgot-password" use:inertia class="text-sm font-medium hover:underline text-base-content/70 hover:text-base-content transition-colors">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-neutral w-full mt-6 shadow-lg shadow-neutral/20" disabled={$form.processing}>
            {#if $form.processing}
                <span class="loading loading-spinner loading-sm"></span>
            {:else}
                Sign In
            {/if}
        </button>

        <div class="text-center mt-6 text-sm text-base-content/60">
            Don't have an account? 
            <a href="/register" use:inertia class="font-semibold text-base-content hover:underline transition-colors">Create one</a>
        </div>
    </form>
</AuthLayout>
