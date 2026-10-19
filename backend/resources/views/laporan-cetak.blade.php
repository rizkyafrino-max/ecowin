@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $kg = fn ($v) => number_format((float) $v, 2, ',', '.').' kg';
    $tgl = fn ($d) => $d ? $d->translatedFormat('d F Y') : null;
    $periode = $dari || $sampai
        ? ($tgl($dari) ?? 'Awal').' s/d '.($tgl($sampai) ?? 'Sekarang')
        : 'Seluruh periode';
    $lingkup = $bank ? $bank->nama_bank_sampah.' (RT '.$bank->rt.' / RW '.$bank->rw.')' : 'Semua Bank Sampah';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Laporan EcoWin — {{ $lingkup }} — {{ $periode }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ecowin-emblem.png') }}">
    <style>
        :root{--em:#047857;--ink:#0F172A;--mut:#475569;--line:#CBD5E1;--soft:#ECFDF5}
        *{box-sizing:border-box}
        body{margin:0;background:#E8EEF2;color:var(--ink);font-family:'Inter Variable',Inter,ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;font-size:12px;line-height:1.5;-webkit-print-color-adjust:exact;print-color-adjust:exact}
        .toolbar{position:sticky;top:0;z-index:5;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 20px;background:#fff;border-bottom:1px solid var(--line)}
        .toolbar b{font-size:14px}.toolbar span{color:var(--mut);font-size:12px}
        .btn{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 18px;border:0;border-radius:10px;background:var(--em);color:#fff;font:inherit;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none}
        .btn.alt{background:#fff;color:var(--ink);border:1.5px solid #8392A6}
        .sheet{width:210mm;min-height:297mm;margin:18px auto;padding:16mm 15mm;background:#fff;box-shadow:0 10px 40px rgba(15,23,42,.14)}
        .head{display:flex;align-items:center;gap:12px;padding-bottom:12px;border-bottom:3px solid var(--em)}
        .head img{width:46px;height:46px;border-radius:12px;background:#000;padding:6px}
        .head h1{margin:0;font-size:20px;letter-spacing:-.01em}.head h1 i{font-style:normal;color:var(--em)}
        .head p{margin:2px 0 0;color:var(--mut)}
        .meta{display:grid;grid-template-columns:repeat(2,1fr);gap:6px 24px;margin:14px 0 4px;padding:12px 14px;border:1px solid var(--line);border-radius:10px;background:#F8FAFC}
        .meta div span{display:block;color:var(--mut);font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700}
        .meta div b{font-size:12.5px}
        h2{margin:20px 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.08em;color:var(--em);break-after:avoid}
        .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
        .card{border:1px solid var(--line);border-radius:10px;padding:10px 12px;break-inside:avoid}
        .card h3{margin:0 0 6px;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--em)}
        .row{display:flex;justify-content:space-between;gap:8px;padding:3px 0;border-bottom:1px dashed #E2E8F0}
        .row:last-child{border-bottom:0}.row span{color:var(--mut)}.row b{text-align:right}
        table{width:100%;border-collapse:collapse;font-size:11px}
        th{padding:6px 7px;background:var(--soft);color:#065F46;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.05em;border-bottom:1.5px solid #6EE7B7}
        td{padding:5px 7px;border-bottom:1px solid #E2E8F0;vertical-align:top}
        tr{break-inside:avoid}
        tfoot td{font-weight:800;border-top:1.5px solid var(--ink);border-bottom:0;background:#F8FAFC}
        .num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
        .note{margin:6px 0 0;color:var(--mut);font-size:10.5px}
        .empty{padding:10px;color:var(--mut);text-align:center;border:1px dashed var(--line);border-radius:8px}
        .sign{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:34px;break-inside:avoid}
        .sign div{text-align:center}.sign .line{height:64px;border-bottom:1px solid var(--ink);margin:0 14px 4px}
        .foot{margin-top:18px;padding-top:8px;border-top:1px solid var(--line);color:var(--mut);font-size:10px;display:flex;justify-content:space-between}
        @page{size:A4;margin:12mm}
        @media print{
            body{background:#fff}.toolbar{display:none}
            .sheet{width:auto;min-height:0;margin:0;padding:0;box-shadow:none}
            a{color:inherit;text-decoration:none}
        }
        @media(max-width:820px){.sheet{width:auto;margin:0;padding:16px}.grid3{grid-template-columns:1fr}.meta{grid-template-columns:1fr}.toolbar span{display:none}table{display:block;overflow-x:auto}}
    </style>
</head>
<body>
<div class="toolbar">
    <div><b>Pratinjau laporan</b> <span>· {{ $lingkup }} · {{ $periode }}</span></div>
    <div style="display:flex;gap:8px">
        <a class="btn alt" href="{{ url('/admin/laporan') }}">Kembali</a>
        <button class="btn" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
</div>

<main class="sheet">
    <header class="head">
        <img src="{{ asset('images/ecowin-emblem.png') }}" alt="" width="46" height="46">
        <div>
            <h1>Eco<i>Win</i> · Laporan Bank Sampah</h1>
            <p>Bank Sampah Digital Berkelanjutan</p>
        </div>
    </header>

    <section class="meta" aria-label="Keterangan laporan">
        <div><span>Cakupan</span><b>{{ $lingkup }}</b></div>
        <div><span>Periode</span><b>{{ $periode }}</b></div>
        <div><span>Dicetak oleh</span><b>{{ $user->nama }} ({{ $user->isAdmin() ? 'Admin' : 'Petugas' }})</b></div>
        <div><span>Tanggal cetak</span><b>{{ now()->translatedFormat('d F Y, H:i') }} WIB</b></div>
    </section>

    <h2>Ringkasan</h2>
    <div class="grid3">
        <div class="card">
            <h3>Anorganik</h3>
            <div class="row"><span>Total berat</span><b>{{ $kg($r['anorganik']['total_berat_kg']) }}</b></div>
            <div class="row"><span>Jumlah transaksi</span><b>{{ number_format($r['anorganik']['jumlah_transaksi']) }}</b></div>
            <div class="row"><span>Total nilai</span><b>{{ $rp($r['anorganik']['total_nilai']) }}</b></div>
            <div class="row"><span>Jenis terbanyak</span><b>{{ $r['anorganik']['jenis_terbanyak'] ?? '-' }}</b></div>
        </div>
        <div class="card">
            <h3>Organik</h3>
            <div class="row"><span>Total berat</span><b>{{ $kg($r['organik']['total_berat_kg']) }}</b></div>
            <div class="row"><span>Aktivitas Biopori</span><b>{{ number_format($r['organik']['jumlah_aktivitas_biopori']) }}</b></div>
            <div class="row"><span>Terverifikasi</span><b>{{ number_format($r['organik']['biopori_terverifikasi']) }}</b></div>
            <div class="row"><span>Estimasi kompos</span><b>{{ $kg($r['organik']['estimasi_kompos_kg']) }}</b></div>
        </div>
        <div class="card">
            <h3>Keuangan</h3>
            <div class="row"><span>Saldo nasabah</span><b>{{ $rp($r['keuangan']['total_saldo_nasabah']) }}</b></div>
            <div class="row"><span>Kredit</span><b>{{ $rp($r['keuangan']['transaksi_masuk']) }}</b></div>
            <div class="row"><span>Debit</span><b>{{ $rp($r['keuangan']['transaksi_keluar']) }}</b></div>
            <div class="row"><span>Penarikan</span><b>{{ $rp($r['keuangan']['total_penarikan']) }}</b></div>
            <div class="row"><span>Penarikan pending</span><b>{{ number_format($r['keuangan']['penarikan_pending']) }}</b></div>
        </div>
    </div>

    @if (count($rekap) > 0)
        <h2>Rekap per Bank Sampah</h2>
        <table>
            <thead><tr><th>Bank Sampah</th><th class="num">Nasabah</th><th class="num">Anorganik (kg)</th><th class="num">Nilai anorganik</th><th class="num">Organik (kg)</th><th class="num">Penarikan</th><th class="num">Saldo nasabah</th></tr></thead>
            <tbody>
                @foreach ($rekap as $b)
                    <tr>
                        <td><b>{{ $b['bank'] }}</b><br><span style="color:var(--mut)">{{ $b['rtrw'] }}</span></td>
                        <td class="num">{{ number_format($b['nasabah']) }}</td>
                        <td class="num">{{ number_format($b['anorganik_kg'], 2, ',', '.') }}</td>
                        <td class="num">{{ $rp($b['anorganik_nilai']) }}</td>
                        <td class="num">{{ number_format($b['organik_kg'], 2, ',', '.') }}</td>
                        <td class="num">{{ $rp($b['penarikan']) }}</td>
                        <td class="num">{{ $rp($b['saldo']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num">{{ number_format(collect($rekap)->sum('nasabah')) }}</td>
                    <td class="num">{{ number_format(collect($rekap)->sum('anorganik_kg'), 2, ',', '.') }}</td>
                    <td class="num">{{ $rp(collect($rekap)->sum('anorganik_nilai')) }}</td>
                    <td class="num">{{ number_format(collect($rekap)->sum('organik_kg'), 2, ',', '.') }}</td>
                    <td class="num">{{ $rp(collect($rekap)->sum('penarikan')) }}</td>
                    <td class="num">{{ $rp(collect($rekap)->sum('saldo')) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <h2>Setoran anorganik</h2>
    @if ($detail['anorganik']->isEmpty())
        <p class="empty">Tidak ada transaksi pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>Tanggal</th><th>Nasabah</th><th>Jenis</th>@if (! $bank)<th>Bank Sampah</th>@endif<th class="num">Berat</th><th class="num">Harga/kg</th><th class="num">Total</th></tr></thead>
            <tbody>
                @foreach ($detail['anorganik'] as $t)
                    <tr>
                        <td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $t->nasabah?->nama }}<br><span style="color:var(--mut)">{{ $t->nasabah?->nomor_nasabah }}</span></td>
                        <td>{{ $t->hargaSampah?->jenisSampah?->nama_jenis ?? '-' }}</td>
                        @if (! $bank)<td>{{ $t->bankSampah?->nama_bank_sampah }}</td>@endif
                        <td class="num">{{ number_format((float) $t->berat_kg, 2, ',', '.') }} kg</td>
                        <td class="num">{{ $rp($t->harga_per_kg) }}</td>
                        <td class="num">{{ $rp($t->nilai_rupiah) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="{{ $bank ? 3 : 4 }}">Jumlah ({{ $detail['anorganik']->count() }} transaksi)</td><td class="num">{{ number_format((float) $detail['anorganik']->sum('berat_kg'), 2, ',', '.') }} kg</td><td></td><td class="num">{{ $rp($detail['anorganik']->sum('nilai_rupiah')) }}</td></tr></tfoot>
        </table>
        @if ($detail['terpotong']['anorganik'])<p class="note">Menampilkan {{ $detail['batas'] }} transaksi terbaru. Persempit periode untuk melihat seluruhnya.</p>@endif
    @endif

    <h2>Setoran organik</h2>
    @if ($detail['organik']->isEmpty())
        <p class="empty">Tidak ada setoran organik pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>Tanggal</th><th>Nasabah</th><th>Jenis organik</th>@if (! $bank)<th>Bank Sampah</th>@endif<th class="num">Berat</th><th class="num">Estimasi kompos</th></tr></thead>
            <tbody>
                @foreach ($detail['organik'] as $t)
                    <tr>
                        <td>{{ ($t->tanggal ?? $t->created_at)?->format('d/m/Y') }}</td>
                        <td>{{ $t->nasabah?->nama }}<br><span style="color:var(--mut)">{{ $t->nasabah?->nomor_nasabah }}</span></td>
                        <td>{{ $t->jenis_organik }}</td>
                        @if (! $bank)<td>{{ $t->bankSampah?->nama_bank_sampah }}</td>@endif
                        <td class="num">{{ number_format((float) $t->berat_kg, 2, ',', '.') }} kg</td>
                        <td class="num">{{ number_format((float) $t->estimasi_kompos_kg, 2, ',', '.') }} kg</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="{{ $bank ? 3 : 4 }}">Jumlah ({{ $detail['organik']->count() }} setoran)</td><td class="num">{{ number_format((float) $detail['organik']->sum('berat_kg'), 2, ',', '.') }} kg</td><td class="num">{{ number_format((float) $detail['organik']->sum('estimasi_kompos_kg'), 2, ',', '.') }} kg</td></tr></tfoot>
        </table>
        @if ($detail['terpotong']['organik'])<p class="note">Menampilkan {{ $detail['batas'] }} setoran terbaru. Persempit periode untuk melihat seluruhnya.</p>@endif
    @endif

    <h2>Penarikan saldo</h2>
    @if ($detail['penarikan']->isEmpty())
        <p class="empty">Tidak ada penarikan pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>Diajukan</th><th>Nasabah</th>@if (! $bank)<th>Bank Sampah</th>@endif<th>Status</th><th class="num">Jumlah</th></tr></thead>
            <tbody>
                @foreach ($detail['penarikan'] as $t)
                    <tr>
                        <td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $t->nasabah?->nama }}<br><span style="color:var(--mut)">{{ $t->nasabah?->nomor_nasabah }}</span></td>
                        @if (! $bank)<td>{{ $t->bankSampah?->nama_bank_sampah }}</td>@endif
                        <td>{{ ucfirst((string) $t->status) }}</td>
                        <td class="num">{{ $rp($t->jumlah) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="{{ $bank ? 3 : 4 }}">Jumlah ({{ $detail['penarikan']->count() }} penarikan)</td><td class="num">{{ $rp($detail['penarikan']->sum('jumlah')) }}</td></tr></tfoot>
        </table>
        @if ($detail['terpotong']['penarikan'])<p class="note">Menampilkan {{ $detail['batas'] }} penarikan terbaru. Persempit periode untuk melihat seluruhnya.</p>@endif
    @endif

    <section class="sign" aria-label="Pengesahan">
        <div><p>Dibuat oleh,</p><div class="line"></div><b>{{ $user->nama }}</b><br><span style="color:var(--mut)">{{ $user->isAdmin() ? 'Admin' : 'Petugas Bank Sampah' }}</span></div>
        <div><p>Mengetahui,</p><div class="line"></div><b>&nbsp;</b><br><span style="color:var(--mut)">Pengurus / Ketua</span></div>
    </section>

    <footer class="foot"><span>EcoWin · Dokumen ini dibuat otomatis dari sistem.</span><span>Rahasia: hanya untuk keperluan pengelolaan Bank Sampah.</span></footer>
</main>

@if ($cetakOtomatis)
    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
@endif
</body>
</html>
