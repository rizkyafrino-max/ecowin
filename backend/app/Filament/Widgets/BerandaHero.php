<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Laporan;
use App\Filament\Resources\AktivitasBioporis\AktivitasBioporiResource;
use App\Filament\Resources\BankSampahs\BankSampahResource;
use App\Filament\Resources\HargaSampahs\HargaSampahResource;
use App\Filament\Resources\Nasabahs\NasabahResource;
use App\Filament\Resources\PenarikanSaldos\PenarikanSaldoResource;
use App\Filament\Resources\TransaksiAnorganiks\TransaksiAnorganikResource;
use App\Filament\Resources\TransaksiOrganiks\TransaksiOrganikResource;
use App\Models\AktivitasBiopori;
use App\Models\BankSampah;
use App\Models\HargaSampah;
use App\Models\JenisSampah;
use App\Models\KoreksiTransaksi;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/**
 * Beranda ala aplikasi mobile: sapaan, pencarian, banner ringkasan (carousel), menu cepat,
 * peringkat dan kartu jenis sampah teratas. Semua data lewat scope visibleTo(), jadi Petugas
 * hanya melihat Bank Sampah miliknya.
 */
class BerandaHero extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.beranda-hero';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isStaff();
    }

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $pending = [
            'biopori' => AktivitasBiopori::query()->visibleTo($user)->where('status', AktivitasBiopori::STATUS_PENDING)->count(),
            'penarikan' => PenarikanSaldo::query()->visibleTo($user)->where('status', PenarikanSaldo::STATUS_PENDING)->count(),
            'koreksi' => KoreksiTransaksi::query()->visibleTo($user)->where('status', KoreksiTransaksi::STATUS_PENDING)->count(),
            'nasabah' => Nasabah::query()->visibleTo($user)->where('status_verifikasi', 'pending')->where('status', 'aktif')->count(),
        ];

        // Pop-up pengingat: kategori yang punya tugas menunggu, lengkap dengan beberapa item terbaru yang langsung menuju halamannya.
        $filterNasabah = ['tableFilters' => ['status_verifikasi' => ['value' => 'pending']]];
        $nasabahBaru = Nasabah::query()->visibleTo($user)->where('status_verifikasi', 'pending')->where('status', 'aktif')->latest('id')->limit(3)->get()
            ->map(fn (Nasabah $n) => ['title' => $n->nama, 'meta' => 'Mendaftar '.$n->created_at?->format('d M Y'), 'url' => NasabahResource::getUrl('index', $filterNasabah + ['tableSearch' => $n->nama])]);
        $penarikan = PenarikanSaldo::query()->visibleTo($user)->with('nasabah')->where('status', PenarikanSaldo::STATUS_PENDING)->latest('id')->limit(3)->get()
            ->map(fn (PenarikanSaldo $p) => ['title' => ($p->nasabah?->nama ?? 'Nasabah').' · Rp '.number_format((float) $p->jumlah, 0, ',', '.'), 'meta' => 'Diajukan '.$p->created_at?->format('d M Y'), 'url' => PenarikanSaldoResource::getUrl('index')]);
        $organik = AktivitasBiopori::query()->visibleTo($user)->with('nasabah')->where('status', AktivitasBiopori::STATUS_PENDING)->latest('id')->limit(3)->get()
            ->map(fn (AktivitasBiopori $a) => ['title' => ($a->nasabah?->nama ?? 'Nasabah').' · '.($a->jenis_sampah ?: 'Aktivitas'), 'meta' => number_format((float) $a->berat_kg, 1, ',', '.').' kg', 'url' => AktivitasBioporiResource::getUrl('view', ['record' => $a])]);
        $koreksi = KoreksiTransaksi::query()->visibleTo($user)->where('status', KoreksiTransaksi::STATUS_PENDING)->latest('id')->limit(3)->get()
            ->map(fn (KoreksiTransaksi $k) => ['title' => ucfirst($k->tipe_transaksi).' #'.$k->transaksi_id, 'meta' => 'Menunggu keputusan', 'url' => \App\Filament\Resources\KoreksiTransaksis\KoreksiTransaksiResource::getUrl('index')]);

        $popup = array_values(array_filter([
            ['label' => 'Pendaftaran nasabah baru', 'hint' => 'Perlu diverifikasi sebelum bisa menarik saldo', 'count' => $pending['nasabah'], 'url' => NasabahResource::getUrl('index', $filterNasabah), 'items' => $nasabahBaru->all()],
            ['label' => 'Penarikan saldo', 'hint' => 'Menunggu persetujuan', 'count' => $pending['penarikan'], 'url' => PenarikanSaldoResource::getUrl('index'), 'items' => $penarikan->all()],
            ['label' => 'Aktivitas organik', 'hint' => 'Menunggu verifikasi foto bukti', 'count' => $pending['biopori'], 'url' => AktivitasBioporiResource::getUrl('index'), 'items' => $organik->all()],
            ['label' => 'Koreksi transaksi', 'hint' => 'Menunggu keputusan', 'count' => $pending['koreksi'], 'url' => \App\Filament\Resources\KoreksiTransaksis\KoreksiTransaksiResource::getUrl('index'), 'items' => $koreksi->all()],
        ], fn (array $p): bool => $p['count'] > 0));
        $jam = (int) now()->format('G');

        return [
            'user' => $user,
            'sapaan' => match (true) {
                $jam < 11 => 'Selamat pagi',
                $jam < 15 => 'Selamat siang',
                $jam < 18 => 'Selamat sore',
                default => 'Selamat malam',
            },
            'lokasi' => $user->isPetugas()
                ? ($user->bankSampah?->nama_bank_sampah ?? '-').' · RT '.($user->bankSampah?->rt ?? '-').'/RW '.($user->bankSampah?->rw ?? '-')
                : 'Kamu mengelola seluruh Bank Sampah EcoWin',
            'pending' => $pending,
            'popup' => $popup,
            'totalPending' => array_sum($pending),
            'urlPending' => $pending['biopori'] > 0 ? AktivitasBioporiResource::getUrl('index')
                : ($pending['penarikan'] > 0 ? PenarikanSaldoResource::getUrl('index') : null),
            'urlNasabah' => NasabahResource::getUrl('index'),
            'urlSetoran' => TransaksiAnorganikResource::canCreate() ? TransaksiAnorganikResource::getUrl('create') : TransaksiAnorganikResource::getUrl('index'),
            'urlLaporan' => Laporan::getUrl(),
            'kpi' => $this->kpi($user),
            'menu' => $this->menu($user),
            'deretan' => $this->deretan($user),
            'jenis' => $this->jenisTeratas($user),
        ];
    }

    /**
     * @return list<array{label:string,url:string,icon:string}>
     */
    private function menu(User $user): array
    {
        $menu = [
            ['label' => 'Nasabah', 'url' => NasabahResource::getUrl('index'), 'icon' => 'heroicon-o-users'],
            ['label' => 'Anorganik', 'url' => TransaksiAnorganikResource::getUrl('index'), 'icon' => 'heroicon-o-archive-box'],
            ['label' => 'Organik', 'url' => TransaksiOrganikResource::getUrl('index'), 'icon' => 'heroicon-o-sparkles'],
            ['label' => 'Biopori', 'url' => AktivitasBioporiResource::getUrl('index'), 'icon' => 'heroicon-o-beaker'],
            ['label' => 'Penarikan', 'url' => PenarikanSaldoResource::getUrl('index'), 'icon' => 'heroicon-o-banknotes'],
            ['label' => 'Laporan', 'url' => Laporan::getUrl(), 'icon' => 'heroicon-o-chart-bar'],
        ];

        if ($user->isAdmin()) {
            array_splice($menu, 1, 0, [['label' => 'Bank Sampah', 'url' => BankSampahResource::getUrl('index'), 'icon' => 'heroicon-o-building-storefront']]);
            $menu[] = ['label' => 'Harga', 'url' => HargaSampahResource::getUrl('index'), 'icon' => 'heroicon-o-tag'];
        }

        return $menu;
    }

    /**
     * @return array{saldo:float,setoran_hari_ini:int,berat:float,nasabah:int}
     */
    private function kpi(User $user): array
    {
        return [
            'saldo' => (float) Nasabah::query()->visibleTo($user)->sum('saldo'),
            'setoran_hari_ini' => TransaksiAnorganik::query()->visibleTo($user)->whereDate('created_at', today())->count(),
            'berat' => (float) TransaksiAnorganik::query()->visibleTo($user)->sum('berat_kg'),
            'nasabah' => Nasabah::query()->visibleTo($user)->where('status', 'aktif')->count(),
        ];
    }

    /**
     * Admin: Bank Sampah dengan setoran terbanyak. Petugas: nasabah dengan saldo terbesar di Bank Sampahnya.
     *
     * @return array{judul:string,url:string,items:list<array{nama:string,nilai:string,persen:int}>}
     */
    private function deretan(User $user): array
    {
        if ($user->isAdmin()) {
            $berat = TransaksiAnorganik::query()
                ->join('nasabah', 'nasabah.id', '=', 'transaksi_anorganik.nasabah_id')
                ->selectRaw('nasabah.bank_sampah_id, SUM(transaksi_anorganik.berat_kg) as total')
                ->groupBy('nasabah.bank_sampah_id')
                ->pluck('total', 'bank_sampah_id');

            $rows = BankSampah::query()->get()
                ->map(fn (BankSampah $b) => ['nama' => $b->nama_bank_sampah, 'angka' => (float) ($berat[$b->id] ?? 0), 'nilai' => number_format((float) ($berat[$b->id] ?? 0), 1, ',', '.').' kg']);

            return ['judul' => 'Bank Sampah teratas', 'url' => BankSampahResource::getUrl('index'), 'items' => $this->peringkat($rows)];
        }

        $rows = Nasabah::query()->visibleTo($user)->orderByDesc('saldo')->limit(8)->get()
            ->map(fn (Nasabah $n) => ['nama' => (string) $n->nama, 'angka' => (float) $n->saldo, 'nilai' => 'Rp '.number_format((float) $n->saldo, 0, ',', '.')]);

        return ['judul' => 'Nasabah teratas', 'url' => NasabahResource::getUrl('index'), 'items' => $this->peringkat($rows)];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{nama:string,angka:float,nilai:string}>  $rows
     * @return list<array{nama:string,nilai:string,persen:int}>
     */
    private function peringkat($rows): array
    {
        $rows = $rows->sortByDesc('angka')->take(6)->values();
        $maks = max(1.0, (float) $rows->max('angka'));

        return $rows->map(fn (array $r) => ['nama' => $r['nama'], 'nilai' => $r['nilai'], 'persen' => (int) round($r['angka'] / $maks * 100)])->all();
    }

    /**
     * @return list<array{nama:string,kategori:string,harga:string,satuan:string,berat:string,url:?string}>
     */
    private function jenisTeratas(User $user): array
    {
        $totalBerat = TransaksiAnorganik::query()->visibleTo($user)
            ->join('harga_sampah', 'harga_sampah.id', '=', 'transaksi_anorganik.harga_sampah_id')
            ->select('harga_sampah.jenis_sampah_id', DB::raw('SUM(transaksi_anorganik.berat_kg) as total'))
            ->groupBy('harga_sampah.jenis_sampah_id')
            ->pluck('total', 'jenis_sampah_id');

        $harga = HargaSampah::query()->berlaku()
            ->select('jenis_sampah_id', DB::raw('MIN(harga_per_kg) as terendah'), DB::raw('MAX(harga_per_kg) as tertinggi'))
            ->groupBy('jenis_sampah_id')
            ->get()->keyBy('jenis_sampah_id');

        $urlTambah = TransaksiAnorganikResource::canCreate() ? TransaksiAnorganikResource::getUrl('create') : null;

        return JenisSampah::query()->with('kategori')->where('status', 'aktif')->get()
            ->sortByDesc(fn (JenisSampah $j) => (float) ($totalBerat[$j->id] ?? 0))
            ->take(8)
            ->map(function (JenisSampah $j) use ($totalBerat, $harga, $urlTambah): array {
                $h = $harga[$j->id] ?? null;
                $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');

                return [
                    'nama' => $j->nama_jenis,
                    'kategori' => $j->kategori?->nama_kategori ?? '-',
                    'harga' => $h === null ? 'Belum ada harga'
                        : ($h->terendah === $h->tertinggi ? $rp($h->terendah) : $rp($h->terendah).' – '.$rp($h->tertinggi)),
                    'satuan' => 'Per 1 '.strtoupper($j->satuan ?? 'kg'),
                    'berat' => number_format((float) ($totalBerat[$j->id] ?? 0), 2, ',', '.').' kg terkumpul',
                    'url' => $urlTambah,
                ];
            })->values()->all();
    }
}
