<script>
    (function () {
        var stored = localStorage.getItem('theme') || @json($theme ?? 'system');
        var isDark = stored === 'dark' || (stored === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', isDark);
        // data-theme dipakai daisyUI buat pilih theme block ('yado'/'yado-dark'
        // di app.css, nama custom biar gak bentrok sama preset bawaan daisyUI) - harus nilai
        // resolved, bukan 'system'. Preferensi mentah user (buat toggle 3-arah) disimpan
        // terpisah di data-theme-pref.
        document.documentElement.dataset.theme = isDark ? 'yado-dark' : 'yado';
        document.documentElement.dataset.themePref = stored;
    })();
</script>
