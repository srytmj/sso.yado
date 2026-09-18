import { createInertiaApp } from '@inertiajs/svelte';
import { mount } from 'svelte';
import { gsap } from 'gsap';

// Global GSAP defaults
gsap.defaults({
    ease: "power3.out",
    duration: 0.6
});

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true });
        let page = pages['./Pages/' + name + '.svelte'];
        return page.default || page;
    },
    setup({ el, App, props }) {
        mount(App, { target: el, props });
    },
});
