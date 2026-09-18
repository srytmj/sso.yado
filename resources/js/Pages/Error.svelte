<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { Link, page } from '@inertiajs/svelte';

    export let status;

    let container;

    const messages = {
        403: {
            title: 'No Permission',
            body: 'Kamu tidak punya akses ke halaman ini.',
        },
        404: {
            title: 'Not Found',
            body: 'Halaman yang kamu cari tidak ditemukan.',
        },
        419: {
            title: 'Session Expired',
            body: 'Sesi kamu sudah kedaluwarsa, silakan muat ulang halaman.',
        },
        429: {
            title: 'Too Many Requests',
            body: 'Terlalu banyak permintaan, coba lagi sebentar lagi.',
        },
        500: {
            title: 'Server Error',
            body: 'Terjadi kesalahan pada server. Coba lagi nanti.',
        },
        503: {
            title: 'Unavailable',
            body: 'Layanan sedang dalam pemeliharaan. Coba lagi nanti.',
        },
    };

    $: message = messages[status] || { title: 'Error', body: 'Terjadi kesalahan yang tidak terduga.' };
    $: user = $page.props.auth?.user;
    $: homeHref = user ? (user.role?.slug === 'superadmin' ? '/dashboard' : '/account') : '/';

    onMount(() => {
        if (container) {
            gsap.from(container.children, {
                y: 20,
                opacity: 0,
                duration: 0.6,
                stagger: 0.1,
                ease: 'power3.out',
            });
        }
    });
</script>

<svelte:head>
    <title>{status} {message.title} - SSO Yado</title>
</svelte:head>

<div class="min-h-screen flex flex-col items-center justify-center bg-base-100 text-base-content p-6">
    <div bind:this={container} class="max-w-md text-center space-y-4">
        <p class="text-sm font-mono tracking-widest uppercase text-base-content/40">Error {status}</p>
        <h1 class="text-4xl font-serif italic tracking-tight text-base-content">{message.title}</h1>
        <p class="text-base-content/60">{message.body}</p>
        <div class="pt-4">
            <Link href={homeHref} class="btn btn-neutral rounded-xl">
                {user ? 'Kembali' : 'Ke Halaman Utama'}
            </Link>
        </div>
    </div>
</div>
