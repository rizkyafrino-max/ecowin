<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#ECFDF5">
    <title>Pindai Kartu - EcoWin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ecowin-emblem.png') }}">
        <style>
        :root{--em:#047857;--em-l:#10B981;--em-d:#065F46;--mint:#ECFDF5;--mint2:#D1FAE5;--ink:#0F172A;--mut:#475569;--line:#CBD5E1;--bg:#F1F8F4}
        *{box-sizing:border-box}
        html,body{margin:0;min-height:100%}
        body{background:var(--bg);color:var(--ink);font-family:'Inter Variable',Inter,ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;-webkit-font-smoothing:antialiased}
        button{font:inherit}

        .page{min-height:100dvh;display:flex;flex-direction:column;align-items:center}
        .top{width:100%;max-width:1040px;display:none;align-items:center;gap:12px;padding:22px 24px 4px}
        .top img{width:40px;height:40px;border-radius:12px;background:#000;padding:5px}
        .top b{font-size:1.2rem;font-weight:800;letter-spacing:-.01em}.top b i{font-style:normal;color:var(--em)}
        .top span{color:var(--mut);font-size:.9rem;margin-left:4px}
        .layout{width:100%;max-width:1040px;display:flex;justify-content:center;gap:24px;padding:0;flex:1}

        /* ===== Kartu pemindai ===== */
        .scan{position:relative;width:100%;max-width:520px;min-height:100dvh;display:flex;flex-direction:column;padding:max(10px,env(safe-area-inset-top)) 18px max(18px,env(safe-area-inset-bottom));background:#fff;overflow:hidden}
        .bar{display:flex;align-items:center;justify-content:space-between;height:52px}
        .bar h1{margin:0;font-size:1.02rem;font-weight:800;letter-spacing:-.01em}
        .ico-btn{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border:0;border-radius:999px;background:transparent;color:var(--ink);cursor:pointer;text-decoration:none}
        .ico-btn:hover{background:var(--mint)}
        .ico-btn svg{width:22px;height:22px}
        a:focus-visible,button:focus-visible,input:focus-visible{outline:3px solid var(--em-l);outline-offset:2px}

        .stage{position:relative;margin:10px auto 0;width:100%;max-width:420px;aspect-ratio:4/5;flex:none}
        .frame{position:absolute;inset:20px;border-radius:24px;overflow:hidden;background:linear-gradient(160deg,#064E3B,#065F46 55%,#047857);box-shadow:inset 0 0 0 1px rgba(255,255,255,.08)}
        #reader{position:absolute;inset:0;width:100%;height:100%;border:0!important;padding:0!important;background:transparent!important}
        #reader__scan_region{position:absolute!important;inset:0;width:100%!important;height:100%!important;min-height:0!important;background:transparent!important}
        #reader video{position:absolute!important;inset:0;width:100%!important;height:100%!important;object-fit:cover;border-radius:0}
        #reader canvas,#reader img{display:none!important}
        .corner{position:absolute;width:58px;height:58px;border:6px solid var(--em);pointer-events:none}
        .corner.tl{top:0;left:0;border-right:0;border-bottom:0;border-top-left-radius:30px}
        .corner.tr{top:0;right:0;border-left:0;border-bottom:0;border-top-right-radius:30px}
        .corner.bl{bottom:0;left:0;border-right:0;border-top:0;border-bottom-left-radius:30px}
        .corner.br{bottom:0;right:0;border-left:0;border-top:0;border-bottom-right-radius:30px}
        .line{position:absolute;left:10px;right:10px;top:0;height:3px;border-radius:3px;background:linear-gradient(90deg,transparent,#6EE7B7 20%,#fff 50%,#6EE7B7 80%,transparent);box-shadow:0 0 8px 2px rgba(110,231,183,.8);transform:translateY(16px);animation:sweep 2.6s ease-in-out infinite;will-change:transform;pointer-events:none;z-index:3}
        .live .line{display:block}.idle .line,.done .line,.wait .line{display:none}
        @keyframes sweep{0%,100%{transform:translateY(16px)}50%{transform:translateY(calc(var(--lh,360px) - 19px))}}
        .state{position:absolute;inset:0;z-index:2;display:none;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:26px;text-align:center;color:#ECFDF5}
        .wait .state,.idle .state,.done .state{display:flex}
        .state .badge{width:64px;height:64px;border-radius:20px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center}
        .state .badge svg{width:30px;height:30px}
        .state b{font-size:1rem;font-weight:800}
        .state p{margin:0;font-size:.85rem;line-height:1.55;color:#A7F3D0;max-width:30ch}
        .spin{width:30px;height:30px;border-radius:999px;border:3px solid rgba(255,255,255,.25);border-top-color:#fff;animation:spin .8s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}
        .wait .badge,.idle .spin,.done .spin{display:none}.wait .spin{display:block}.wait .badge{display:none}
        .live .state{display:none}

        .hint{margin:18px 14px 0;text-align:center;color:var(--mut);font-size:.9rem;line-height:1.55}
        #status{min-height:1.5em;margin:8px 14px 0;text-align:center;font-size:.82rem;font-weight:700;color:var(--em)}
        #status.error{color:#B91C1C}

        .controls{margin-top:auto;padding-top:20px;display:flex;align-items:flex-end;justify-content:space-between}
        .left{display:flex;gap:18px}
        .act{display:flex;flex-direction:column;align-items:center;gap:6px;border:0;background:none;color:var(--mut);font-size:.72rem;font-weight:700;cursor:pointer;padding:0}
        .round{width:56px;height:56px;border-radius:999px;border:1.5px solid var(--line);background:#fff;color:var(--ink);display:inline-flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(15,23,42,.08);transition:background .15s ease,border-color .15s ease}
        .round svg{width:22px;height:22px}
        .act:hover .round{background:var(--mint);border-color:var(--em-l)}
        .act[aria-pressed="true"] .round{background:#FEF9C3;border-color:#FACC15}
        .act[disabled]{opacity:.45;cursor:not-allowed}
        .go{width:76px;height:76px;border-radius:999px;border:0;background:linear-gradient(135deg,var(--em-l),var(--em));color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 0 0 7px #fff,0 0 0 8px var(--line),0 14px 28px rgba(4,120,87,.35)}
        .go svg{width:34px;height:34px}
        .go:active{transform:scale(.96)}

        /* ===== Lembar bawah (HP) / panel samping (desktop): token manual & hasil ===== */
        .sheet-bg{position:fixed;inset:0;background:rgba(15,23,42,.5);opacity:0;pointer-events:none;transition:opacity .2s ease;z-index:20}
        .sheet-bg.on{opacity:1;pointer-events:auto}
        .sheet{position:fixed;left:50%;bottom:0;z-index:21;width:100%;max-width:520px;transform:translate(-50%,105%);transition:transform .28s cubic-bezier(.2,.8,.2,1);background:#fff;border-radius:28px 28px 0 0;padding:10px 20px max(22px,env(safe-area-inset-bottom));box-shadow:0 -12px 40px rgba(15,23,42,.2)}
        .sheet.on{transform:translate(-50%,0)}
        .grab{width:40px;height:5px;border-radius:99px;background:var(--line);margin:0 auto 14px}
        .sheet h2{margin:0 0 4px;font-size:1.1rem;font-weight:800}
        .sheet p{margin:0;color:var(--mut);font-size:.88rem;line-height:1.5}
        .field{display:flex;gap:10px;margin-top:14px}
        .field input{flex:1;min-width:0;height:50px;padding:0 14px;border:1.5px solid #8392A6;border-radius:14px;font:inherit;font-size:1rem;font-weight:500;background:#fff}
        .field input::placeholder{color:#64748B;font-weight:400}
        .field input:focus{outline:0;border-color:var(--em);box-shadow:0 0 0 4px rgba(16,185,129,.2)}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:50px;padding:0 20px;border:0;border-radius:14px;background:var(--em);color:#fff;font-weight:700;font-size:.95rem;text-decoration:none;cursor:pointer}
        .btn:hover{background:var(--em-d)}
        .btn.soft{background:var(--mint);color:var(--em-d)}.btn.soft:hover{background:var(--mint2)}
        .btn.outline{background:#fff;color:var(--ink);border:1.5px solid #8392A6}.btn.outline:hover{background:var(--bg)}
        .who{display:flex;align-items:center;gap:14px;margin-top:14px;padding:14px;border-radius:18px;background:var(--mint);border:1px solid var(--mint2)}
        .who .av{width:48px;height:48px;border-radius:999px;background:#fff;color:var(--em);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.2rem;flex:none;box-shadow:0 1px 3px rgba(15,23,42,.1)}
        .who b{display:block;font-size:1rem}.who span{display:block;font-size:.82rem;color:var(--mut)}
        .who .saldo{margin-left:auto;text-align:right;font-weight:800;color:var(--em-d);font-size:.98rem}
        .acts{display:grid;gap:10px;margin-top:14px}

        .side{display:none}
        .steps{margin:12px 0 0;padding:0;list-style:none;display:grid;gap:12px}
        .steps li{display:flex;gap:12px;align-items:flex-start;color:var(--mut);font-size:.9rem;line-height:1.5}
        .steps i{flex:none;width:28px;height:28px;border-radius:999px;background:var(--mint2);color:var(--em-d);font-style:normal;font-weight:800;font-size:.82rem;display:flex;align-items:center;justify-content:center}

        /* Desktop: kartu pemindai + panel samping, latar lembut */
        @media(min-width:900px){
            body{background:radial-gradient(900px 480px at 85% -10%,#D1FAE5,transparent 70%),radial-gradient(700px 420px at 0% 100%,#ECFDF5,transparent 70%),var(--bg)}
            .top{display:flex}
            .layout{align-items:flex-start;padding:16px 24px 40px}
            .scan{min-height:0;border-radius:32px;border:1px solid #E2E8F0;box-shadow:0 24px 60px rgba(6,95,70,.12);padding-bottom:26px}
            .side{display:flex;flex-direction:column;gap:18px;width:380px;flex:none}
            .panel{background:#fff;border:1px solid #E2E8F0;border-radius:28px;padding:22px;box-shadow:0 18px 44px rgba(6,95,70,.08)}
            .panel h2{margin:0 0 2px;font-size:1.05rem;font-weight:800}
            .panel p.sub{margin:0;color:var(--mut);font-size:.88rem;line-height:1.5}
            .sheet-bg,.sheet-slot{display:none!important}
            #more{visibility:hidden}
        }
        @media(prefers-reduced-motion:reduce){.line,.spin{animation:none}.line{transform:translateY(50%)}.sheet,.sheet-bg{transition:none}}
    </style>
</head>
<body>
<div class="page">
    <header class="top">
        <img src="{{ asset('images/ecowin-emblem.png') }}" alt="" width="40" height="40">
        <b>Eco<i>Win</i></b><span>· Pindai Kartu Nasabah</span>
    </header>

    <div class="layout">
        <main class="scan" id="app">
            <header class="bar">
                <a class="ico-btn" href="/admin" aria-label="Kembali ke dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <h1>Pindai Kartu</h1>
                <button type="button" class="ico-btn" id="more" aria-label="Masukkan token manual" aria-haspopup="dialog">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>
                </button>
            </header>

            <section class="stage wait" id="stage" aria-label="Pemindai kartu QR">
                <div class="frame">
                    <div id="reader"></div>
                    <div class="state" id="state" aria-live="polite">
                        <span class="spin" aria-hidden="true"></span>
                        <span class="badge" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/></svg></span>
                        <b id="state-title">Menyiapkan kamera…</b>
                        <p id="state-text">Izinkan akses kamera bila diminta oleh browser.</p>
                    </div>
                    <span class="line" aria-hidden="true"></span>
                </div>
                <span class="corner tl" aria-hidden="true"></span><span class="corner tr" aria-hidden="true"></span>
                <span class="corner bl" aria-hidden="true"></span><span class="corner br" aria-hidden="true"></span>
            </section>

            <p class="hint">Letakkan kode QR kartu nasabah di dalam bingkai untuk memindai secara otomatis.</p>
            <p id="status" role="status" aria-live="polite">Menyiapkan pemindai…</p>

            <div class="controls">
                <div class="left">
                    <button type="button" class="act" id="gallery" aria-label="Pindai dari gambar">
                        <span class="round"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></span>
                        Galeri
                    </button>
                    <button type="button" class="act" id="torch" aria-label="Nyalakan senter" aria-pressed="false" disabled>
                        <span class="round"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg></span>
                        Senter
                    </button>
                </div>
                <button type="button" class="go" id="go" aria-label="Pindai ulang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 12v4a1 1 0 0 1-1 1h-4"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M17 21h2a2 2 0 0 0 2-2v-2"/><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M7 17h.01"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="5" height="5" rx="1"/></svg>
                </button>
            </div>
            <input type="file" id="file" accept="image/*" hidden>
        </main>

        {{-- Panel samping (desktop): cara memindai + token manual + hasil --}}
        <aside class="side" aria-label="Panduan dan hasil">
            <section class="panel">
                <h2>Cara memindai</h2>
                <ol class="steps">
                    <li><i>1</i><span>Arahkan kamera ke QR pada kartu nasabah.</span></li>
                    <li><i>2</i><span>Tunggu sampai terbaca otomatis.</span></li>
                    <li><i>3</i><span>Pilih aksi: setor anorganik, setor organik, atau pengambilan saldo.</span></li>
                </ol>
            </section>
            <section class="panel" aria-labelledby="t-manual-d">
                <h2 id="t-manual-d">Token manual</h2>
                <p class="sub">Gunakan bila kamera tidak tersedia atau QR sulit terbaca.</p>
                <div class="field"><input id="token-d" placeholder="Token QR kartu" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="64" aria-label="Token QR kartu"><button type="button" class="btn" id="lookup-d">Cari</button></div>
            </section>
            <section class="panel" id="panel-result" hidden aria-live="polite">
                <h2>Kartu terbaca</h2>
                <div id="result-d"></div>
                <div style="margin-top:12px"><button type="button" class="btn outline" id="again-d" style="width:100%">Pindai kartu lain</button></div>
            </section>
        </aside>
    </div>
</div>

{{-- Lembar bawah (HP): token manual --}}
<div class="sheet-bg" id="bg"></div>
<section class="sheet sheet-slot" id="sheet-manual" role="dialog" aria-modal="true" aria-labelledby="t-manual" aria-hidden="true">
    <div class="grab"></div>
    <h2 id="t-manual">Masukkan token manual</h2>
    <p>Gunakan bila kamera tidak tersedia atau QR sulit terbaca.</p>
    <div class="field"><input id="token" placeholder="Token QR kartu" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="64" aria-label="Token QR kartu"><button type="button" class="btn" id="lookup">Cari</button></div>
</section>

{{-- Lembar bawah (HP): hasil --}}
<section class="sheet sheet-slot" id="sheet-result" role="dialog" aria-modal="true" aria-labelledby="t-result" aria-hidden="true">
    <div class="grab"></div>
    <h2 id="t-result">Kartu terbaca</h2>
    <div id="result"></div>
    <div style="margin-top:12px"><button type="button" class="btn outline" id="again" style="width:100%">Pindai kartu lain</button></div>
</section>

<script>
const $ = (id) => document.getElementById(id);
const statusEl = $('status'), stage = $('stage'), bg = $('bg');
const desktop = () => window.matchMedia('(min-width:900px)').matches;
let scanner = null, running = false, busy = false, torchOn = false, cooldown = 0;

function esc(v) { return String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
function say(text, isError = false) { statusEl.textContent = text; statusEl.className = isError ? 'error' : ''; }
function mode(m, title, text) {
    stage.classList.remove('wait', 'live', 'idle', 'done');
    stage.classList.add(m);
    if (title) $('state-title').textContent = title;
    if (text !== undefined) $('state-text').textContent = text;
}

function openSheet(id) { closeSheets(); $(id).classList.add('on'); $(id).setAttribute('aria-hidden', 'false'); bg.classList.add('on'); }
function closeSheets() { document.querySelectorAll('.sheet').forEach((s) => { s.classList.remove('on'); s.setAttribute('aria-hidden', 'true'); }); bg.classList.remove('on'); }
bg.addEventListener('click', () => { closeSheets(); if (!running) start(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSheets(); });

function hasilHtml(n) {
    const id = encodeURIComponent(n.id), saldo = new Intl.NumberFormat('id-ID').format(n.saldo);
    return `<div class="who"><span class="av">${esc((n.nama || '?').trim().charAt(0).toUpperCase())}</span><div><b>${esc(n.nama)}</b><span>${esc(n.nomor_nasabah)}</span></div><div class="saldo">Rp ${esc(saldo)}</div></div>
        <div class="acts">
            <a class="btn" href="/admin/transaksi-anorganiks/create?nasabah_id=${id}">Setor anorganik</a>
            <a class="btn soft" href="/admin/organik/transaksi-organiks/create?nasabah_id=${id}">Setor organik</a>
            <a class="btn outline" href="/admin/penarikan-saldos/create?nasabah_id=${id}">Pengambilan saldo</a>
        </div>`;
}
function showResult(payload) {
    const html = hasilHtml(payload.data);
    $('result').innerHTML = html; $('result-d').innerHTML = html;
    if (desktop()) { $('panel-result').hidden = false; $('panel-result').scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
    else openSheet('sheet-result');
}
function clearResult() { $('result').innerHTML = ''; $('result-d').innerHTML = ''; $('panel-result').hidden = true; }

async function lookup(token) {
    token = String(token || '').trim();
    if (!token) { say('QR atau token belum terbaca.', true); return; }
    if (busy || Date.now() < cooldown) return;
    busy = true;
    say('Memuat data nasabah…');
    try {
        const response = await fetch('/admin/scan-kartu/' + encodeURIComponent(token), { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Kartu tidak terdaftar atau bukan wilayah petugas.');
        const payload = await response.json();
        await stop();
        mode('done', 'Kartu berhasil dibaca', 'Pilih aksi untuk nasabah ini.');
        say('Kartu berhasil dibaca.');
        showResult(payload);
    } catch (error) {
        say(error.message || 'Gagal memuat data nasabah.', true);
        cooldown = Date.now() + 2500; // jangan memanggil server berulang untuk QR yang sama
    } finally {
        busy = false;
    }
}

/* ===== Mesin pemindai =====
   Kamera dikendalikan di sini (video sendiri, tampil "cover"), dengan dua cara membaca QR:
   1) BarcodeDetector bawaan browser (Chrome Android dll.): paling ringan.
   2) jsQR (pustaka kecil, dimuat hanya bila dibutuhkan): dipakai di browser tanpa BarcodeDetector (mis. Safari/iPhone). */
class PemindaiKamera {
    constructor(host) { this.host = host; this.video = null; this.stream = null; this.timer = null; this.sibuk = false; this.kanvas = null; }

    /** Membaca satu frame (video/bitmap); mengembalikan teks QR atau null. Diisi oleh turunan. */
    async baca(sumber) { return null; }

    async start(onText) {
        this.stream = await navigator.mediaDevices.getUserMedia({ audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } });
        this.video = document.createElement('video');
        this.video.setAttribute('playsinline', ''); this.video.muted = true; this.video.srcObject = this.stream;
        this.host.appendChild(this.video);
        await this.video.play();
        // Pemindaian berurutan dengan jeda adaptif: di perangkat lambat, jeda diperpanjang (maks. ~25% waktu CPU) agar halaman tetap responsif.
        const putaran = async () => {
            if (!this.video) return;
            let dur = 0;
            if (this.video.readyState >= 2 && !document.hidden) {
                const t0 = performance.now();
                try { const teks = await this.baca(this.video); if (teks) { onText(teks); return; } } catch (e) { /* abaikan satu frame */ }
                dur = performance.now() - t0;
            }
            if (this.video) this.timer = setTimeout(putaran, Math.max(this.jeda, dur * 3));
        };
        this.timer = setTimeout(putaran, this.jeda);
    }

    async stop() {
        clearTimeout(this.timer); this.timer = null;
        if (this.stream) this.stream.getTracks().forEach((t) => t.stop());
        this.stream = null;
        if (this.video) { this.video.srcObject = null; this.video.remove(); this.video = null; }
    }

    senterDidukung() { const t = this.stream && this.stream.getVideoTracks()[0]; const c = t && t.getCapabilities ? t.getCapabilities() : null; return !!(c && c.torch); }
    async senter(on) { await this.stream.getVideoTracks()[0].applyConstraints({ advanced: [{ torch: on }] }); }

    async bacaFile(file) {
        const bmp = await createImageBitmap(file);
        try { const teks = await this.baca(bmp); if (!teks) throw new Error('kosong'); return teks; } finally { if (bmp.close) bmp.close(); }
    }
}

class PemindaiNative extends PemindaiKamera {
    constructor(host) { super(host); this.jeda = 180; this.detector = new BarcodeDetector({ formats: ['qr_code'] }); }
    async baca(sumber) { const k = await this.detector.detect(sumber); return k.length && k[0].rawValue ? k[0].rawValue : null; }
}

class PemindaiJsQR extends PemindaiKamera {
    constructor(host) { super(host); this.jeda = 250; }
    baca(sumber) {
        const w0 = sumber.videoWidth || sumber.width, h0 = sumber.videoHeight || sumber.height;
        if (!w0 || !h0) return null;
        // Diperkecil (sisi terpanjang 480 px) agar ringan; QR kartu tetap terbaca jelas.
        const skala = Math.min(1, 480 / Math.max(w0, h0)), w = Math.round(w0 * skala), h = Math.round(h0 * skala);
        if (!this.kanvas) this.kanvas = document.createElement('canvas');
        this.kanvas.width = w; this.kanvas.height = h;
        const ctx = this.kanvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(sumber, 0, 0, w, h);
        const hasil = jsQR(ctx.getImageData(0, 0, w, h).data, w, h, { inversionAttempts: 'dontInvert' });
        return hasil && hasil.data ? hasil.data : null;
    }
}

let pustakaMuat = null;
function muatPustaka() {
    if (window.jsQR) return Promise.resolve();
    if (!pustakaMuat) pustakaMuat = new Promise((ok, gagal) => {
        const el = document.createElement('script');
        el.src = @json(asset('vendor/jsQR.js'));
        el.onload = () => ok(); el.onerror = () => { pustakaMuat = null; gagal(new Error('pustaka')); };
        document.head.appendChild(el);
    });
    return pustakaMuat;
}

async function nativeTersedia() {
    try { return 'BarcodeDetector' in window && (await BarcodeDetector.getSupportedFormats()).includes('qr_code'); } catch (e) { return false; }
}

/** Membuat mesin pemindai yang sesuai; null bila tidak ada yang bisa dipakai. */
async function buatPemindai() {
    const host = $('reader');
    if (await nativeTersedia()) return new PemindaiNative(host);
    try { await muatPustaka(); } catch (e) { return null; }
    return new PemindaiJsQR(host);
}

async function stop() {
    if (scanner && running) { try { await scanner.stop(); } catch (e) { /* abaikan */ } }
    running = false; torchOn = false;
    $('torch').setAttribute('aria-pressed', 'false'); $('torch').disabled = true;
}


function ukurFrame() { const f = document.querySelector('.frame'); if (f) f.style.setProperty('--lh', f.clientHeight + 'px'); }
window.addEventListener('resize', ukurFrame);
function kameraTersedia() {
    return window.isSecureContext && navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function';
}

async function start() {
    if (!kameraTersedia()) {
        mode('idle', 'Kamera butuh koneksi aman', 'Buka halaman ini lewat https:// atau http://localhost. Anda tetap bisa memindai dari gambar atau memakai token manual.');
        say('Kamera diblokir browser pada koneksi tidak aman (HTTP).', true); return;
    }
    if (running) return;
    ukurFrame();
    mode('wait', 'Menyiapkan kamera…', 'Izinkan akses kamera bila diminta oleh browser.');
    say('Menyiapkan pemindai…');
    if (!scanner) scanner = await buatPemindai();
    if (!scanner) {
        mode('idle', 'Pemindai tidak termuat', 'Periksa koneksi internet, lalu muat ulang halaman. Anda tetap bisa memakai token manual.');
        say('Pemindai tidak termuat.', true); return;
    }
    try {
        await scanner.start((text) => lookup(text));
        running = true;
        ukurFrame();
        mode('live');
        say('Kamera aktif. Arahkan ke QR kartu.');
        if (scanner.senterDidukung()) $('torch').disabled = false;
    } catch (error) {
        const ditolak = /Permission|NotAllowed|denied/i.test(String(error));
        const tidakAda = /NotFound|Requested device/i.test(String(error));
        mode('idle', ditolak ? 'Akses kamera ditolak' : tidakAda ? 'Kamera tidak ditemukan' : 'Kamera tidak dapat dibuka',
            ditolak ? 'Izinkan kamera lewat ikon gembok di address bar, lalu tekan tombol pindai ulang.' : 'Tutup aplikasi lain yang memakai kamera, lalu tekan tombol pindai ulang.');
        say('Kamera tidak dapat dibuka. Gunakan gambar atau token manual.', true);
    }
}


$('go').addEventListener('click', async () => { closeSheets(); clearResult(); await stop(); await start(); });
$('again').addEventListener('click', async () => { closeSheets(); clearResult(); await start(); });
$('again-d').addEventListener('click', async () => { clearResult(); await start(); });
$('more').addEventListener('click', () => { openSheet('sheet-manual'); setTimeout(() => $('token').focus(), 300); });
$('lookup').addEventListener('click', () => { closeSheets(); lookup($('token').value); });
$('lookup-d').addEventListener('click', () => lookup($('token-d').value));
$('token').addEventListener('keydown', (e) => { if (e.key === 'Enter') { closeSheets(); lookup($('token').value); } });
$('token-d').addEventListener('keydown', (e) => { if (e.key === 'Enter') lookup($('token-d').value); });

$('torch').addEventListener('click', async () => {
    try {
        torchOn = !torchOn;
        await scanner.senter(torchOn);
        $('torch').setAttribute('aria-pressed', String(torchOn));
        $('torch').setAttribute('aria-label', torchOn ? 'Matikan senter' : 'Nyalakan senter');
    } catch (e) { torchOn = false; say('Senter tidak didukung di perangkat ini.', true); }
});

$('gallery').addEventListener('click', () => $('file').click());
$('file').addEventListener('change', async (e) => {
    const file = e.target.files && e.target.files[0];
    e.target.value = '';
    if (!file) return;
    await stop();
    say('Membaca gambar…');
    try {
        if (!scanner) scanner = await buatPemindai();
        if (!scanner) throw new Error('tidak ada pemindai');
        const text = await scanner.bacaFile(file);
        await lookup(text);
    } catch (err) {
        say('QR tidak ditemukan pada gambar. Coba foto yang lebih jelas.', true);
        await start();
    }
});

// Kamera dijeda saat tab disembunyikan (hemat baterai/CPU) dan dilanjutkan saat kembali.
let tadiLive = false;
window.addEventListener('pagehide', () => { stop(); });
document.addEventListener('visibilitychange', () => {
    if (document.hidden) { tadiLive = running; if (tadiLive) { stop(); mode('wait', 'Kamera dijeda', 'Kembali ke halaman ini untuk melanjutkan.'); } }
    else if (tadiLive) { tadiLive = false; start(); }
});
document.addEventListener('DOMContentLoaded', start);
</script>
</body>
</html>
