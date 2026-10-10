<?php

namespace App\Services;

use App\Models\KoreksiTransaksi;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Koreksi transaksi: Petugas mengajukan (dengan alasan + berat yang benar),
 * Admin menyetujui/menolak. Tidak ada edit transaksi secara bebas.
 */
class KoreksiService
{
    public function __construct(
        private SaldoService $saldo,
        private PriceResolver $prices,
        private KomposCalculator $kompos,
        private AuditLogger $audit,
    ) {}

    public function ajukan(User $actor, string $tipe, int $transaksiId, float $beratBaru, string $alasan): KoreksiTransaksi
    {
        if (! $actor->isStaff()) {
            throw new AuthorizationException('Hanya petugas/admin yang dapat mengajukan koreksi.');
        }

        $transaksi = $tipe === 'anorganik' ? TransaksiAnorganik::find($transaksiId) : TransaksiOrganik::find($transaksiId);

        if (! $transaksi || ! $actor->canManageBankSampah($transaksi->bank_sampah_id)) {
            // 404 agar keberadaan transaksi Bank Sampah lain tidak bocor.
            throw ValidationException::withMessages(['transaksi_id' => ['Transaksi tidak ditemukan.']]);
        }

        if (KoreksiTransaksi::query()->where('tipe_transaksi', $tipe)->where('transaksi_id', $transaksiId)->where('status', KoreksiTransaksi::STATUS_PENDING)->exists()) {
            throw ValidationException::withMessages(['transaksi_id' => ['Masih ada koreksi pending untuk transaksi ini.']]);
        }

        $koreksi = new KoreksiTransaksi;
        $koreksi->forceFill([
            'transaksi_id' => $transaksiId,
            'tipe_transaksi' => $tipe,
            'bank_sampah_id' => $transaksi->bank_sampah_id,
            'diajukan_oleh' => $actor->id,
            'status' => KoreksiTransaksi::STATUS_PENDING,
            'alasan' => $alasan,
            'data_sebelum' => ['berat_kg' => (float) $transaksi->berat_kg],
            'data_koreksi' => ['berat_kg' => $beratBaru],
        ])->save();

        $this->audit->log('ajukan_koreksi_transaksi', $koreksi, null, $koreksi->attributesToArray(), $actor);

        return $koreksi;
    }

    public function setujui(User $actor, KoreksiTransaksi $koreksi, ?string $catatan = null): KoreksiTransaksi
    {
        $this->assertCanDecide($actor, $koreksi);

        return DB::transaction(function () use ($actor, $koreksi, $catatan): KoreksiTransaksi {
            $locked = KoreksiTransaksi::query()->whereKey($koreksi->id)->lockForUpdate()->firstOrFail();
            $this->assertPending($locked);

            $transaksi = $locked->transaksiAsli();

            if (! $transaksi) {
                throw ValidationException::withMessages(['transaksi_id' => ['Transaksi asli tidak ditemukan.']]);
            }

            $beratBaru = (float) ($locked->data_koreksi['berat_kg'] ?? $transaksi->berat_kg);
            $before = $transaksi->attributesToArray();

            if ($transaksi instanceof TransaksiAnorganik) {
                $hargaPerKg = (float) ($transaksi->harga_per_kg ?? $transaksi->hargaSampah?->harga_per_kg ?? 0);
                $nilaiBaru = (int) round($beratBaru * $hargaPerKg);
                $selisih = $nilaiBaru - (int) $transaksi->nilai_rupiah;

                $transaksi->forceFill(['berat_kg' => $beratBaru, 'nilai_rupiah' => $nilaiBaru])->save();

                if ($selisih > 0) {
                    $this->saldo->kredit($transaksi->nasabah, $selisih, $locked, 'Koreksi transaksi #'.$transaksi->id, $actor);
                } elseif ($selisih < 0) {
                    $this->saldo->debit($transaksi->nasabah, abs($selisih), $locked, 'Koreksi transaksi #'.$transaksi->id, $actor);
                }
            } else {
                $transaksi->forceFill([
                    'berat_kg' => $beratBaru,
                    'estimasi_kompos_kg' => $this->kompos->estimasi($beratBaru, $transaksi->metode_pengolahan),
                ])->save();
            }

            $this->audit->log('ubah_transaksi', $transaksi, $before, $transaksi->attributesToArray(), $actor, 'Koreksi #'.$locked->id);

            $lockedBefore = $locked->attributesToArray();
            $locked->forceFill([
                'status' => KoreksiTransaksi::STATUS_APPROVED,
                'disetujui_oleh' => $actor->id,
                'catatan_admin' => $catatan,
                'diputuskan_at' => now(),
            ])->save();

            $this->audit->log('approve_koreksi_transaksi', $locked, $lockedBefore, $locked->attributesToArray(), $actor);

            return $locked;
        });
    }

    public function tolak(User $actor, KoreksiTransaksi $koreksi, string $catatan): KoreksiTransaksi
    {
        $this->assertCanDecide($actor, $koreksi);

        return DB::transaction(function () use ($actor, $koreksi, $catatan): KoreksiTransaksi {
            $locked = KoreksiTransaksi::query()->whereKey($koreksi->id)->lockForUpdate()->firstOrFail();
            $this->assertPending($locked);
            $before = $locked->attributesToArray();

            $locked->forceFill([
                'status' => KoreksiTransaksi::STATUS_REJECTED,
                'disetujui_oleh' => $actor->id,
                'catatan_admin' => $catatan,
                'diputuskan_at' => now(),
            ])->save();

            $this->audit->log('reject_koreksi_transaksi', $locked, $before, $locked->attributesToArray(), $actor);

            return $locked;
        });
    }

    /** Petugas lain di Bank Sampah koreksi tersebut (atau Admin); pengaju tidak boleh memutuskan koreksinya sendiri. */
    private function assertCanDecide(User $actor, KoreksiTransaksi $koreksi): void
    {
        if (! $actor->isStaff() || ! $actor->isActive() || ! $actor->canManageBankSampah($koreksi->bank_sampah_id)) {
            throw new AuthorizationException('Anda tidak berhak memutuskan koreksi transaksi ini.');
        }

        if ((int) $koreksi->diajukan_oleh === (int) $actor->id) {
            throw new AuthorizationException('Koreksi harus diputuskan oleh petugas lain di Bank Sampah ini, bukan pengaju.');
        }
    }
    private function assertPending(KoreksiTransaksi $koreksi): void
    {
        if ($koreksi->status !== KoreksiTransaksi::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => ['Koreksi sudah diputuskan.']]);
        }
    }
}
