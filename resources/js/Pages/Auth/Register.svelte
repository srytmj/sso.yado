<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { inertia, useForm } from '@inertiajs/svelte';
    import AuthLayout from '../../Layouts/AuthLayout.svelte';

    let formElements;
    let title;

    const form = useForm({
        name: '',
        username: '',
        email: '',
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
        $form.post('/register');
    }
</script>

<svelte:head>
    <title>Register - SSO Yado</title>
</svelte:head>

<AuthLayout>
    <h2 bind:this={title} class="text-2xl font-semibold mb-6 text-base-content">Create Account</h2>
    
    <form on:submit|preventDefault={submit} bind:this={formElements} class="space-y-4">
        
        <div class="form-control">
            <label class="label"><span class="label-text font-medium">Name (Optional)</span></label>
            <input type="text" bind:value={$form.name} class="input input-bordered w-full bg-base-100 transition-colors focus:border-base-content" />
            {#if $form.errors.name}<p class="text-xs text-error mt-1">{$form.errors.name}</p>{/if}
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-medium">Username</span></label>
            <input type="text" bind:value={$form.username} required class="input input-bordered w-full bg-base-100 transition-colors focus:border-base-content" />
            {#if $form.errors.username}<p class="text-xs text-error mt-1">{$form.errors.username}</p>{/if}
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-medium">Email Address</span></label>
            <input type="email" bind:value={$form.email} required class="input input-bordered w-full bg-base-100 transition-colors focus:border-base-content" />
            {#if $form.errors.email}<p class="text-xs text-error mt-1">{$form.errors.email}</p>{/if}
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-medium">Password</span></label>
            <input type="password" bind:value={$form.password} required class="input input-bordered w-full bg-base-100 transition-colors focus:border-base-content" />
            {#if $form.errors.password}<p class="text-xs text-error mt-1">{$form.errors.password}</p>{/if}
        </div>
        
        <div class="form-control">
            <label class="label"><span class="label-text font-medium">Confirm Password</span></label>
            <input type="password" bind:value={$form.password_confirmation} required class="input input-bordered w-full bg-base-100 transition-colors focus:border-base-content" />
        </div>

        <button type="submit" class="btn btn-neutral w-full mt-6 shadow-lg shadow-neutral/20" disabled={$form.processing}>
            {#if $form.processing}
                <span class="loading loading-spinner loading-sm"></span>
            {:else}
                Register
            {/if}
        </button>

        <div class="text-center mt-6 text-sm text-base-content/60">
            Already have an account? 
            <a href="/login" use:inertia class="font-semibold text-base-content hover:underline transition-colors">Sign In</a>
        </div>
    </form>
</AuthLayout>
