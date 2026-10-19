{{-- Layar peluncuran panel Admin/Petugas: tampil sekali per sesi browser, bukan di tiap pindah halaman. --}}
<div id="ecl-launch" role="status" aria-live="polite" aria-label="Memuat EcoWin">
    <style>
        #ecl-launch{position:fixed;inset:0;z-index:100000;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#F8FAFC;overflow:hidden;opacity:1;transition:opacity .35s ease;font-family:'Inter Variable',Inter,ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}
        #ecl-launch.out{opacity:0;pointer-events:none}
        #ecl-launch .bg{position:absolute;inset:0;pointer-events:none}
        #ecl-launch .bg i{position:absolute;display:block;border-radius:58% 42% 61% 39% / 46% 55% 45% 54%;background:#D1FAE5;opacity:.55}
        #ecl-launch .bg i:nth-child(1){width:min(46vw,520px);height:min(46vw,520px);top:-12%;right:-10%}
        #ecl-launch .bg i:nth-child(2){width:min(38vw,420px);height:min(38vw,420px);bottom:-14%;left:-9%;background:#ECFDF5;opacity:.9;border-radius:41% 59% 47% 53% / 55% 42% 58% 45%}
        #ecl-launch .bg i:nth-child(3){width:min(18vw,200px);height:min(18vw,200px);bottom:18%;right:12%;background:#A7F3D0;opacity:.35}
        #ecl-launch .stage{position:relative;display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 24px}
        #ecl-launch img{width:116px;height:auto;display:block;animation:ecl-breathe 2.6s ease-in-out infinite}
        #ecl-launch .tag{margin:26px 0 0;font-size:.95rem;font-weight:600;color:#047857;letter-spacing:.01em}
        #ecl-launch .bar{margin-top:28px;width:72px;height:4px;border-radius:99px;background:#D1FAE5;overflow:hidden}
        #ecl-launch .bar b{display:block;width:40%;height:100%;border-radius:99px;background:#059669;animation:ecl-slide 1.1s ease-in-out infinite}
        @keyframes ecl-breathe{0%,100%{transform:scale(1)}50%{transform:scale(1.035)}}
        @keyframes ecl-slide{0%{transform:translateX(-110%)}100%{transform:translateX(260%)}}
        @media (prefers-reduced-motion:reduce){#ecl-launch img,#ecl-launch .bar b{animation:none}#ecl-launch .bar b{width:100%}#ecl-launch{transition:none}}
    </style>
    <noscript><style>#ecl-launch{display:none}</style></noscript>

    <div class="bg" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="stage">
        <img src="{{ asset('images/ecowin-logo.png') }}" alt="EcoWin" width="116" height="135">
        <p class="tag">Langkah kecil, dampak besar.</p>
        <div class="bar" aria-hidden="true"><b></b></div>
    </div>

    <script>
        (function () {
            var el = document.getElementById('ecl-launch');
            if (!el) return;
            try {
                // Sudah pernah tampil di sesi ini (pindah halaman / segarkan): jangan tampil lagi, tanpa kedip.
                if (sessionStorage.getItem('ecowin.launched')) { el.remove(); return; }
                sessionStorage.setItem('ecowin.launched', '1');
            } catch (e) { /* penyimpanan diblokir: tetap tampil sekali lalu hilang */ }

            var start = Date.now(), selesai = false;
            function tutup() {
                if (selesai) return;
                selesai = true;
                setTimeout(function () {
                    el.classList.add('out');
                    setTimeout(function () { el.remove(); }, 400);
                }, Math.max(0, 900 - (Date.now() - start)));
            }
            if (document.readyState === 'complete') tutup(); else window.addEventListener('load', tutup);
            setTimeout(tutup, 4000); // pengaman: tidak pernah menahan layar lebih dari 4 detik
        })();
    </script>
</div>
