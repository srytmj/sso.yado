<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { inertia, page } from '@inertiajs/svelte';

    let container;
    let title;
    let subtitle;
    let buttons;

    onMount(() => {
        const tl = gsap.timeline();
        tl.from(title, { y: 30, opacity: 0, duration: 0.8, ease: "power3.out" })
          .from(subtitle, { y: 20, opacity: 0, duration: 0.8, ease: "power3.out" }, "-=0.6")
          .from(buttons.children, { y: 15, opacity: 0, duration: 0.5, stagger: 0.1, ease: "back.out(1.7)" }, "-=0.4");
    });
</script>

<svelte:head>
    <title>Home - SSO Yado</title>
</svelte:head>

<div bind:this={container} class="min-h-screen flex flex-col items-center justify-center bg-base-100 text-base-content p-6">
    <div class="max-w-2xl text-center space-y-6">
        <h1 bind:this={title} class="text-5xl font-bold tracking-tight">SSO Yado</h1>
        <p bind:this={subtitle} class="text-lg text-base-content/70">Central Identity Provider for the Yado ecosystem.</p>
        
        <div bind:this={buttons} class="flex justify-center gap-4 mt-8">
            {#if $page.props.auth.user}
                <a href="/dashboard" use:inertia class="btn btn-neutral">Go to Dashboard</a>
                <a href="/account" use:inertia class="btn btn-outline">My Account</a>
                <form method="POST" action="/logout">
                    <button type="submit" class="btn btn-ghost">Logout</button>
                </form>
            {:else}
                <a href="/login" use:inertia class="btn btn-neutral">Login</a>
                <a href="/register" use:inertia class="btn btn-outline">Register</a>
            {/if}
        </div>
    </div>
</div>
