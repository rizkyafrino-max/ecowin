<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Nasabah - {{ $nasabah->nama }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ecowin-emblem.png') }}">
    <style>
        :root { --hijau: #059669; --hijau-tua: #047857; --tinta: #0f172a; --abu: #64748b; --garis: #e2e8f0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: linear-gradient(#ecfdf5, #f8fafc); color: var(--tinta); font-family: Inter, "Segoe UI", system-ui, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .halaman { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px; padding: 28px 16px; }
        .judul { text-align: center; }
        .judul h1 { margin: 0; font-size: 22px; font-weight: 800; }
        .judul p { margin: 4px 0 0; color: var(--abu); font-size: 14px; }

        /* Kartu: sama dengan Kartu Nasabah di web dan Android (rasio kartu bank 85,6 x 54 mm); isi mengikuti lebar kartu (satuan cqw). */
        .bungkus { width: min(92vw, 620px); container-type: inline-size; }
        .kartu { position: relative; aspect-ratio: 1.586 / 1; width: 100%; overflow: hidden; border-radius: 28px; padding: 6.5cqw; color: #fff;
            background: linear-gradient(135deg, #064E3B, #059669 50%, #34D399); box-shadow: 0 18px 40px -18px rgba(6, 78, 59, .75); outline: 1px solid rgba(255, 255, 255, .25); outline-offset: -1px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .kartu .hias { position: absolute; border-radius: 50%; pointer-events: none; }
        .kartu .hias-1 { right: -12cqw; top: -22cqw; width: 60cqw; height: 60cqw; background: rgba(255, 255, 255, .09); }
        .kartu .hias-2 { left: -10cqw; bottom: -26cqw; width: 44cqw; height: 44cqw; background: rgba(255, 255, 255, .07); }
        .kartu .daun { position: absolute; right: -2cqw; bottom: -4cqw; width: 56cqw; height: 56cqw; color: rgba(255, 255, 255, .07); pointer-events: none; }
        .kartu .isi { position: relative; display: flex; height: 100%; gap: 5cqw; }
        .kartu .kiri { display: flex; flex: 1; min-width: 0; flex-direction: column; justify-content: space-between; }
        .merek { display: flex; align-items: center; gap: 2.4cqw; }
        .merek .lambang { display: flex; align-items: center; justify-content: center; width: 10cqw; height: 10cqw; flex: none; padding: 1.2cqw; border-radius: 2.4cqw; background: #fff; }
        .merek .lambang img { height: 100%; width: auto; }
        .merek b { display: block; font-size: clamp(13px, 4.2cqw, 26px); font-weight: 800; line-height: 1.15; }
        .merek b i { font-style: normal; color: #a7f3d0; }
        .merek small { display: block; font-size: clamp(7px, 2.1cqw, 12px); font-weight: 700; letter-spacing: .1em; color: #a7f3d0; }
        .label { margin: 0; font-size: clamp(7px, 2.3cqw, 13px); font-weight: 700; letter-spacing: .15em; color: #a7f3d0; }
        .nama { margin: .8cqw 0 0; font-size: clamp(14px, 5.6cqw, 40px); font-weight: 800; line-height: 1.15; overflow-wrap: anywhere; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .nomor { margin: 1cqw 0 0; font-family: ui-monospace, "Cascadia Mono", Consolas, monospace; font-size: clamp(10px, 3.6cqw, 22px); font-weight: 600; letter-spacing: .12em; color: #d1fae5; }
        .bank { display: flex; align-items: center; gap: 1.6cqw; margin: 0; font-size: clamp(9px, 2.9cqw, 16px); font-weight: 500; color: rgba(255, 255, 255, .9); }
        .bank svg { flex: none; width: 3.4cqw; height: 3.4cqw; min-width: 12px; min-height: 12px; color: #a7f3d0; }
        .bank span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .kanan { display: flex; flex: none; flex-direction: column; align-items: center; justify-content: center; }
        .qr { width: 37cqw; height: 37cqw; padding: 1.6cqw; border-radius: 3.6cqw; background: #fff; }
        .qr img { display: block; width: 100%; height: 100%; image-rendering: pixelated; }
        .pindai { margin: 1.6cqw 0 0; font-size: clamp(6.5px, 2cqw, 12px); font-weight: 700; letter-spacing: .1em; color: #d1fae5; }

        .aksi { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        .tombol { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 44px; padding: 0 20px; border-radius: 999px; border: 1px solid var(--garis); background: #fff; color: var(--tinta);
            font: inherit; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; transition: background .15s; }
        .tombol:hover { background: #f1f5f9; }
        .tombol.utama { background: var(--hijau); border-color: var(--hijau); color: #fff; }
        .tombol.utama:hover { background: var(--hijau-tua); }
        .tombol:disabled { opacity: .6; cursor: progress; }
        .tombol svg { width: 18px; height: 18px; }
        .catatan { max-width: 460px; margin: 0; text-align: center; color: var(--abu); font-size: 12.5px; line-height: 1.5; }
        .galat { margin: 0; color: #b91c1c; font-size: 13px; font-weight: 600; }

        /* Cetak: hanya kartu, dengan ukuran kartu bank yang sebenarnya (85,6 x 54 mm) dan warna latar tetap tercetak. */
        @page { size: A4; margin: 15mm; }
        @media print {
            body { background: #fff; }
            .halaman { display: block; min-height: 0; padding: 0; }
            .judul, .aksi, .catatan, .galat { display: none !important; }
            .bungkus { width: 85.6mm; }
            .kartu { box-shadow: none; }
        }
    </style>
</head>
<body>
<main class="halaman">
    <div class="judul">
        <h1>Kartu Nasabah</h1>
        <p>{{ $nasabah->nama }} · {{ $nasabah->nomor_nasabah }}</p>
    </div>

    <div class="bungkus">
        <section class="kartu" id="kartu" aria-label="Kartu Nasabah"
                 data-nama="{{ $nasabah->nama }}" data-nomor="{{ $nasabah->nomor_nasabah }}" data-bank="{{ $bank }}"
                 data-qr="{{ $qrDataUri }}" data-emblem="{{ asset('images/ecowin-emblem.png') }}">
            <span class="hias hias-1" aria-hidden="true"></span>
            <span class="hias hias-2" aria-hidden="true"></span>
            <svg class="daun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>

            <div class="isi">
                <div class="kiri">
                    <div class="merek">
                        <span class="lambang"><img src="{{ asset('images/ecowin-emblem.png') }}" alt=""></span>
                        <span><b>Eco<i>Win</i></b><small>BANK SAMPAH DIGITAL</small></span>
                    </div>
                    <div>
                        <p class="label">KARTU NASABAH</p>
                        <p class="nama">{{ $nasabah->nama }}</p>
                        <p class="nomor">{{ $nasabah->nomor_nasabah }}</p>
                    </div>
                    <p class="bank">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>{{ $bank }}</span>
                    </p>
                </div>
                <div class="kanan">
                    <div class="qr"><img src="{{ $qrDataUri }}" alt="QR nasabah {{ $nasabah->nomor_nasabah }}"></div>
                    <p class="pindai">PINDAI OLEH PETUGAS</p>
                </div>
            </div>
        </section>
    </div>

    <div class="aksi">
        <button type="button" class="tombol utama" id="cetak">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
            Cetak kartu
        </button>
        <button type="button" class="tombol" id="unduh-kartu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
            Unduh kartu (PNG)
        </button>
        <a class="tombol" id="unduh-qr" href="{{ $qrDataUri }}" download="QR-{{ preg_replace('/[^A-Za-z0-9-]/', '', (string) $nasabah->nomor_nasabah) }}.png">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="16" y="3" width="5" height="5" rx="1"/><rect x="3" y="16" width="5" height="5" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg>
            Unduh QR saja
        </a>
    </div>
    <p class="galat" id="galat" role="alert" hidden></p>
    <p class="catatan">Tunjukkan kartu ini kepada petugas saat setor sampah atau mengambil saldo. QR hanya berisi kode acak, bukan data pribadi. Saat mencetak, pilih "Grafik latar belakang" bila warna kartu tidak ikut tercetak.</p>
</main>

<script src="{{ asset('js/kartu-png.js') }}"></script>
<script>
    document.getElementById('cetak').addEventListener('click', function () { window.print(); });

    document.getElementById('unduh-kartu').addEventListener('click', async function (e) {
        var tombol = e.currentTarget, kartu = document.getElementById('kartu'), galat = document.getElementById('galat');
        tombol.disabled = true; galat.hidden = true;
        try {
            var blob = await window.buatKartuPng({ nama: kartu.dataset.nama, nomor: kartu.dataset.nomor, bank: kartu.dataset.bank, qr: kartu.dataset.qr, emblem: kartu.dataset.emblem });
            window.unduhBlob(blob, window.namaBerkasKartu(kartu.dataset.nomor));
        } catch (err) {
            galat.textContent = 'Kartu gagal dibuat. Coba lagi atau gunakan Cetak kartu.';
            galat.hidden = false;
        } finally { tombol.disabled = false; }
    });
</script>
</body>
</html>
