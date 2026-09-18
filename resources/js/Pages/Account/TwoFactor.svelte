<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { inertia, page } from '@inertiajs/svelte';
    
    let container;
    onMount(() => {
        gsap.from(container, { opacity: 0, y: 20, duration: 0.6, ease: "power3.out" });
    });
</script>

<svelte:head>
    <title>TwoFactor - SSO Yado</title>
</svelte:head>

<div bind:this={container} class="p-8 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">Account/TwoFactor</h1>
    <p class="text-base-content/70 mb-6">This page has been migrated to Svelte + GSAP.</p>
    <a href="/" use:inertia class="btn btn-neutral">Back to Home</a>
</div>
