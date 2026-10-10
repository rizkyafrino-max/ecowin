<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AktivitasBioporis\AktivitasBioporiResource;
use App\Filament\Resources\KoreksiTransaksis\KoreksiTransaksiResource;
use App\Filament\Resources\Nasabahs\NasabahResource;
use App\Filament\Resources\PenarikanSaldos\PenarikanSaldoResource;
use App\Models\AktivitasBiopori;
use App\Models\KoreksiTransaksi;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\User;
use Filament\Widgets\Widget;

/**
 * "Aktivitas Menunggu Persetujuan": Biopori, Penarikan, Koreksi Transaksi, verifikasi Nasabah baru.
 */
class PendingApprovalsTable extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.pending-approvals';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isStaff();
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'groups' => [
                [
                    'label' => 'Aktivitas Biopori',
                    'count' => AktivitasBiopori::query()->visibleTo($user)->where('status', AktivitasBiopori::STATUS_PENDING)->count(),
                    'items' => AktivitasBiopori::query()->visibleTo($user)->with('nasabah')->where('status', AktivitasBiopori::STATUS_PENDING)->latest('id')->limit(5)->get()
                        ->map(fn (AktivitasBiopori $a) => ['title' => $a->nasabah?->nama, 'meta' => $a->tanggal_pemasukan?->format('d M Y H:i').' · '.(float) $a->berat_kg.' kg', 'url' => AktivitasBioporiResource::getUrl('view', ['record' => $a])]),
                    'url' => AktivitasBioporiResource::getUrl('index'),
                ],
                [
                    'label' => 'Pengajuan Penarikan',
                    'count' => PenarikanSaldo::query()->visibleTo($user)->where('status', PenarikanSaldo::STATUS_PENDING)->count(),
                    'items' => PenarikanSaldo::query()->visibleTo($user)->with('nasabah')->where('status', PenarikanSaldo::STATUS_PENDING)->latest('id')->limit(5)->get()
                        ->map(fn (PenarikanSaldo $p) => ['title' => $p->nasabah?->nama, 'meta' => 'Rp '.number_format($p->jumlah, 0, ',', '.'), 'url' => PenarikanSaldoResource::getUrl('index')]),
                    'url' => PenarikanSaldoResource::getUrl('index'),
                ],
                [
                    'label' => 'Koreksi Transaksi',
                    'count' => KoreksiTransaksi::query()->visibleTo($user)->where('status', KoreksiTransaksi::STATUS_PENDING)->count(),
                    'items' => KoreksiTransaksi::query()->visibleTo($user)->with('pengaju')->where('status', KoreksiTransaksi::STATUS_PENDING)->latest('id')->limit(5)->get()
                        ->map(fn (KoreksiTransaksi $k) => ['title' => ucfirst($k->tipe_transaksi).' #'.$k->transaksi_id, 'meta' => 'Diajukan '.$k->pengaju?->nama, 'url' => KoreksiTransaksiResource::getUrl('index')]),
                    'url' => KoreksiTransaksiResource::getUrl('index'),
                ],
                [
                    'label' => 'Verifikasi Nasabah Baru',
                    'count' => Nasabah::query()->visibleTo($user)->where('status_verifikasi', 'pending')->count(),
                    'items' => Nasabah::query()->visibleTo($user)->where('status_verifikasi', 'pending')->latest('id')->limit(5)->get()
                        ->map(fn (Nasabah $n) => ['title' => $n->nama, 'meta' => 'Mendaftar '.$n->created_at?->format('d M Y'), 'url' => NasabahResource::getUrl('index', ['tableFilters' => ['status_verifikasi' => ['value' => 'pending']]])]),
                    'url' => NasabahResource::getUrl('index', ['tableFilters' => ['status_verifikasi' => ['value' => 'pending']]]),
                ],
            ],
        ];
    }
}
