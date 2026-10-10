<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pindai Kartu - EcoWin</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    @include('filament.global-ui-styles')
    <style>
        :root{--green:#176b4d;--soft:#e5f4ec}*{box-sizing:border-box}body{margin:0;background:#f2f7f3;color:#193228;font:16px Arial}.wrap{max-width:760px;margin:0 auto;padding:34px 20px 128px}.panel{background:#fff;border:1px solid #e0ebe3;border-radius:28px;padding:28px;box-shadow:0 18px 50px #176b4d12}h1{color:var(--green);margin:0;font-size:30px;letter-spacing:-.04em}.hint{color:#668274;line-height:1.5}#reader{width:100%;overflow:hidden;border-radius:20px;background:#eff6f1;padding:8px}#reader video{border-radius:16px}.manual{display:flex;gap:8px;margin-top:14px}.manual input{flex:1;padding:13px;border:1px solid #cbdad0;border-radius:12px}.manual button,.actions a{border:0;display:inline-block;padding:12px 16px;background:var(--green);color:#fff;text-decoration:none;border-radius:12px;font-size:13px;cursor:pointer;font-weight:700}.result{margin-top:18px;padding:20px;border-radius:18px;background:var(--soft);border:1px solid #d3eadc}.actions a{margin:12px 8px 0 0}.error{color:#a43b32}
    </style>
</head>
<body>
<main class="wrap"><section class="panel">
    <h1>Pindai Kartu Nasabah</h1>
    <p class="hint">Izinkan akses kamera, lalu arahkan kamera ke QR pada kartu nasabah.</p>
    <div id="reader"></div><p id="status" class="hint">Menyiapkan pemindai…</p>
    <div class="manual"><input id="token" placeholder="Atau masukkan token QR manual"><button id="lookup">Cari</button></div><div id="result"></div>
</section></main>
@include('filament.global-footer')
<script>
const statusEl=document.getElementById('status'),resultEl=document.getElementById('result'),tokenEl=document.getElementById('token');let scanner;
function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function showResult(payload){const n=payload.data;const id=encodeURIComponent(n.id);const saldo=new Intl.NumberFormat('id-ID').format(n.saldo);resultEl.innerHTML=`<div class="result"><b>${esc(n.nama)}</b><br>No. nasabah: ${esc(n.nomor_nasabah)}<br>Saldo: Rp ${esc(saldo)}<div class="actions"><a href="/admin/transaksi-anorganiks/create?nasabah_id=${id}">Setor anorganik</a><a href="/admin/organik/transaksi-organiks/create?nasabah_id=${id}">Setor organik</a><a href="/admin/penarikan-saldos/create?nasabah_id=${id}">Pengambilan saldo</a></div></div>`;}
async function lookup(token){if(!token){statusEl.textContent='QR atau token belum terbaca.';return}statusEl.textContent='Memuat data nasabah…';try{const response=await fetch('/admin/scan-kartu/'+encodeURIComponent(token),{headers:{Accept:'application/json'}});if(!response.ok)throw new Error('Kartu tidak terdaftar atau bukan wilayah petugas.');showResult(await response.json());statusEl.textContent='Kartu berhasil dibaca.';if(scanner)await scanner.stop().catch(()=>{});}catch(error){statusEl.textContent=error.message;statusEl.className='hint error';}}
document.getElementById('lookup').addEventListener('click',()=>lookup(tokenEl.value.trim()));
if(window.Html5Qrcode){scanner=new Html5Qrcode('reader');scanner.start({facingMode:'environment'},{fps:10,qrbox:{width:220,height:220}},decodedText=>lookup(decodedText),()=>{}).then(()=>{statusEl.textContent='Kamera aktif. Arahkan ke QR kartu.'}).catch(error=>{statusEl.textContent='Kamera tidak dapat dibuka: '+error;statusEl.className='hint error';});}else{statusEl.textContent='Pemindai tidak termuat. Gunakan token manual atau periksa koneksi internet.';}
</script>
</body></html>
