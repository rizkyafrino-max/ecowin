{{-- Input desimal berformat Indonesia: diawali 0 langsung berkoma ("05" → "0,5"). Berlaku untuk input ber-atribut data-eco-decimal. --}}
<script>
(function () {
    if (window.__ecoDecimal) return;
    window.__ecoDecimal = true;

    // Aturan sama dengan Web & Android: titik → koma, hanya angka, satu koma, "0" + angka → "0,angka".
    function autoKoma(raw, maxDesimal) {
        var s = String(raw == null ? '' : raw).replace(/\./g, ',').replace(/[^0-9,]/g, '');
        var i = s.indexOf(',');
        var bulat = i === -1 ? s : s.slice(0, i);
        var pecah = i === -1 ? null : s.slice(i + 1).replace(/,/g, '');
        if (pecah === null && bulat.length >= 2 && bulat.charAt(0) === '0') {
            pecah = bulat.slice(1);
            bulat = '0';
        }
        if (bulat.length > 1) bulat = bulat.replace(/^0+(?=\d)/, '');
        if (pecah === null) return bulat;
        return bulat + ',' + pecah.slice(0, maxDesimal);
    }

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!(el instanceof HTMLInputElement) || !el.hasAttribute('data-eco-decimal')) return;
        var max = parseInt(el.getAttribute('data-eco-decimal'), 10) || 2;
        var sebelum = el.value, sesudah = autoKoma(sebelum, max);
        if (sesudah === sebelum) return;
        var caret = el.selectionStart;
        el.value = sesudah;
        try {
            var pos = Math.max(0, (caret || 0) + (sesudah.length - sebelum.length));
            el.setSelectionRange(pos, pos);
        } catch (err) { /* abaikan */ }
    }, true); // fase capture: nilai sudah rapi sebelum dibaca Livewire
})();
</script>
