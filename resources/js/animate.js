import { animate, stagger, eases } from 'animejs'

/**
 * Micro-interaction global pakai anime.js - dipasang sekali di app.js, otomatis
 * jalan di semua halaman tanpa perlu ubah tiap file Blade. CSS transition di
 * app.css tetap ada buat warna/border (murah, selalu jalan), animasi JS di sini
 * khusus buat gerakan yang kerasa "hidup": tombol yang mantul pas ditekan, dan
 * kartu/alert yang masuk dengan stagger pas halaman dimuat.
 *
 * Easing pakai fungsi dari `eases` (bukan string berparameter kayak
 * 'outElastic(1, .6)') - lebih aman, gak gantung ke string-parsing internal
 * anime.js buat argumen ease dengan parameter custom.
 */

const BOUNCE_EASE = eases.outElastic(1, 0.6)
const PRESSABLE = '.btn, .tab, .badge[role="button"]'

function initPressBounce() {
    let pressed = null

    const squash = (el) => {
        pressed = el
        animate(el, {
            scale: 0.93,
            duration: 100,
            ease: 'outQuad',
        })
    }

    const bounceBack = () => {
        if (!pressed) return
        const el = pressed
        pressed = null
        animate(el, {
            scale: [0.93, 1.05, 1],
            duration: 420,
            ease: BOUNCE_EASE,
        })
    }

    document.addEventListener('pointerdown', (e) => {
        const el = e.target.closest(PRESSABLE)
        if (!el || el.disabled) return
        squash(el)
    })
    document.addEventListener('pointerup', bounceBack)
    document.addEventListener('pointerleave', bounceBack, true)
    document.addEventListener('pointercancel', bounceBack)
}

function initEntrance() {
    const els = document.querySelectorAll(
        '.animate-fade-in, .animate-slide-up, .animate-scale-in, main .card, main .alert'
    )
    if (!els.length) return

    animate(els, {
        opacity: [0, 1],
        translateY: [14, 0],
        scale: [0.97, 1],
        delay: stagger(50, { start: 60 }),
        duration: 550,
        ease: 'outExpo',
    })
}

export function initAnimations() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return
    }

    try {
        initPressBounce()
    } catch (e) {
        console.error('[animate] press-bounce init failed', e)
    }

    const runEntrance = () => {
        try {
            initEntrance()
        } catch (e) {
            console.error('[animate] entrance init failed', e)
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runEntrance)
    } else {
        runEntrance()
    }
}
