<script>
(function () {
    // Lencana jumlah filter aktif bernilai 0 tidak berguna dan menimpa ikon filter: sembunyikan.
    var jadwal = false;
    function rapikan() {
        jadwal = false;
        document.querySelectorAll('.fi-ta-filters-dropdown .fi-badge').forEach(function (b) {
            b.classList.toggle('eco-nol', b.textContent.trim() === '0');
        });
    }
    function tunda() { if (!jadwal) { jadwal = true; window.requestAnimationFrame(rapikan); } }
    new MutationObserver(tunda).observe(document.documentElement, { childList: true, subtree: true, characterData: true });
    rapikan();
})();
</script>
