<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';

    let container;
    let card;
    let bg;

    onMount(() => {
        const tl = gsap.timeline();
        tl.from(bg, { opacity: 0, duration: 1, ease: 'power2.inOut' })
          .from(container, { opacity: 0, y: 30, duration: 0.8, ease: 'power3.out' }, '-=0.5')
          .from(card, { scale: 0.95, opacity: 0, duration: 0.8, ease: 'back.out(1.5)' }, '-=0.6');
    });
</script>

<div bind:this={bg} class="min-h-screen bg-base-200 flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Subtle background noise/pattern -->
    <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0); background-size: 32px 32px;"></div>

    <div bind:this={container} class="w-full max-w-md relative z-10">
        <div class="text-center mb-10">
            <h1 class="text-5xl font-serif italic tracking-tight text-base-content mb-3">Yado</h1>
            <p class="text-xs font-semibold tracking-widest text-base-content/50 uppercase">Identity Provider</p>
        </div>

        <div bind:this={card} class="bg-base-100 rounded-2xl shadow-2xl shadow-base-content/5 border border-base-content/10 overflow-hidden">
            <div class="p-8 sm:p-10">
                <slot />
            </div>
        </div>
    </div>
</div>
