const fs = require('fs');
const path = require('path');

const pages = [
    'Auth/Login', 'Auth/Register', 'Auth/ForgotPassword', 'Auth/ResetPassword', 
    'Auth/Invite', 'Auth/TwoFactorChallenge',
    'Account/Show', 'Account/Sessions', 'Account/TwoFactor',
    'Dashboard/Index', 'Dashboard/Applications/Index', 'Dashboard/Applications/Create', 'Dashboard/Applications/Show',
    'Dashboard/Sessions/Index', 'Dashboard/Logs/Index', 'Dashboard/AuditLog/Index',
    'Dashboard/Settings/Index', 'Dashboard/Users/Index', 'Dashboard/Users/Invite',
    'Auth/Verify'
];

pages.forEach(p => {
    const fullPath = path.join('resources/js/Pages', p + '.svelte');
    fs.mkdirSync(path.dirname(fullPath), { recursive: true });
    
    const content = \<script>
    import { onMount } from 'svelte';
    import { gsap } from 'gsap';
    import { inertia, page } from '@inertiajs/svelte';
    
    let container;
    onMount(() => {
        gsap.from(container, { opacity: 0, y: 20, duration: 0.6, ease: "power3.out" });
    });
</script>

<svelte:head>
    <title>\ - SSO Yado</title>
</svelte:head>

<div bind:this={container} class="p-8 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">\</h1>
    <p class="text-base-content/70 mb-6">This page has been migrated to Svelte + GSAP.</p>
    <a href="/" use:inertia class="btn btn-neutral">Back to Home</a>
</div>
\;
    if (!fs.existsSync(fullPath)) {
        fs.writeFileSync(fullPath, content);
        console.log('Created ' + fullPath);
    }
});
