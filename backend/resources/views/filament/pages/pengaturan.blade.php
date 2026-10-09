<x-filament-panels::page>
    <p class="eco-set-note">
        Nilai di bawah dibaca dari konfigurasi server (<code>config/ecowin.php</code>) dan diubah lewat file <code>.env</code>. Rahasia seperti client secret tidak ditampilkan.
    </p>

    <div class="eco-report-grid">
        @foreach ($this->kelompok() as $judul => $baris)
            <div class="eco-report-card">
                <h3>{{ $judul }}</h3>
                @foreach ($baris as $label => $nilai)
                    <div class="eco-report-row"><span>{{ $label }}</span><b>{{ $nilai }}</b></div>
                @endforeach
            </div>
        @endforeach
    </div>

    <style>
        .eco-set-note{font-size:.88rem;color:var(--eco-muted);line-height:1.6}
        .eco-set-note code{padding:.1rem .4rem;border-radius:6px;background:var(--eco-soft);color:var(--eco-primary);font-size:.8rem}
    </style>
</x-filament-panels::page>
