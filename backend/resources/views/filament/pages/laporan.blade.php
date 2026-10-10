@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $kg = fn ($v) => number_format((float) $v, 2, ',', '.').' kg';
@endphp
<x-filament-panels::page>
    {{ $this->filterForm }}

    <div class="eco-report-grid">
        <div class="eco-report-card">
            <h3>Anorganik</h3>
            <div class="eco-report-row"><span>Total berat</span><b>{{ $kg($r['anorganik']['total_berat_kg']) }}</b></div>
            <div class="eco-report-row"><span>Jumlah transaksi</span><b>{{ number_format($r['anorganik']['jumlah_transaksi']) }}</b></div>
            <div class="eco-report-row"><span>Total nilai</span><b>{{ $rp($r['anorganik']['total_nilai']) }}</b></div>
            <div class="eco-report-row"><span>Total saldo nasabah</span><b>{{ $rp($r['keuangan']['total_saldo_nasabah']) }}</b></div>
            <div class="eco-report-row"><span>Jenis sampah terbanyak</span><b>{{ $r['anorganik']['jenis_terbanyak'] ?? '-' }}</b></div>
        </div>
        <div class="eco-report-card">
            <h3>Organik</h3>
            <div class="eco-report-row"><span>Total berat</span><b>{{ $kg($r['organik']['total_berat_kg']) }}</b></div>
            <div class="eco-report-row"><span>Jumlah aktivitas</span><b>{{ number_format($r['organik']['jumlah_aktivitas']) }}</b></div>
            <div class="eco-report-row"><span>Aktivitas Biopori</span><b>{{ number_format($r['organik']['jumlah_aktivitas_biopori']) }}</b></div>
            <div class="eco-report-row"><span>Biopori terverifikasi</span><b>{{ number_format($r['organik']['biopori_terverifikasi']) }}</b></div>
            <div class="eco-report-row"><span>Biopori aktif</span><b>{{ number_format((int) $r['organik']['biopori_aktif']) }}</b></div>
            <div class="eco-report-row"><span>Estimasi hasil kompos</span><b>{{ $kg($r['organik']['estimasi_kompos_kg']) }}</b></div>
        </div>
        <div class="eco-report-card">
            <h3>Keuangan</h3>
            <div class="eco-report-row"><span>Saldo nasabah</span><b>{{ $rp($r['keuangan']['total_saldo_nasabah']) }}</b></div>
            <div class="eco-report-row"><span>Transaksi masuk (kredit)</span><b>{{ $rp($r['keuangan']['transaksi_masuk']) }}</b></div>
            <div class="eco-report-row"><span>Transaksi keluar (debit)</span><b>{{ $rp($r['keuangan']['transaksi_keluar']) }}</b></div>
            <div class="eco-report-row"><span>Total penarikan</span><b>{{ $rp($r['keuangan']['total_penarikan']) }}</b></div>
            <div class="eco-report-row"><span>Penarikan pending</span><b>{{ number_format($r['keuangan']['penarikan_pending']) }}</b></div>
        </div>
    </div>
</x-filament-panels::page>
