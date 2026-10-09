<?php

namespace App\Services;

use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur penarikan: pending -> approved (saldo dikurangi) -> completed, atau rejected.
 */
class PenarikanService
{
    public function __construct(private SaldoService $saldo, private AuditLogger $audit) {}

    public function ajukan(User $actor, Nasabah $nasabah, int $jumlah, ?string $catatan = null): PenarikanSaldo
    {
        $milikSendiri = $actor->isNasabah() && $actor->nasabah?->id === $nasabah->id;

        if (! $milikSendiri && ! ($actor->isStaff() && $actor->canManageBankSampah($nasabah->bank_sampah_id))) {
            throw new AuthorizationException('Anda tidak berhak mengajukan penarikan untuk nasabah ini.');
        }

        // Nasabah yang mendaftar sendiri baru boleh menarik saldo setelah diverifikasi Petugas.
        if ($milikSendiri && $nasabah->status_verifikasi !== 'verified') {
            throw ValidationException::withMessages(['jumlah' => ['Akun Anda belum diverifikasi petugas Bank Sampah, penarikan belum dapat diajukan.']]);
        }

        if ($jumlah < config('ecowin.penarikan.minimal')) {
            throw ValidationException::withMessages(['jumlah' => ['Nominal penarikan minimal Rp '.number_format(config('ecowin.penarikan.minimal'), 0, ',', '.').'.']]);
        }

        return DB::transaction(function () use ($actor, $nasabah, $jumlah, $catatan): PenarikanSaldo {
            /** @var Nasabah $locked */
            $locked = Nasabah::query()->whereKey($nasabah->id)->lockForUpdate()->firstOrFail();

            // Pengajuan pending lain ikut diperhitungkan agar tidak terjadi double-spend.
            if ($jumlah > $locked->saldoTersedia()) {
                throw ValidationException::withMessages(['jumlah' => ['Saldo tidak mencukupi (termasuk pengajuan yang masih pending).']]);
            }

            $penarikan = new PenarikanSaldo;
            $penarikan->forceFill([
                'nasabah_id' => $locked->id,
                'bank_sampah_id' => $locked->bank_sampah_id,
                'jumlah' => $jumlah,
                'status' => PenarikanSaldo::STATUS_PENDING,
                'catatan' => $catatan,
            ])->save();

            $this->audit->log('ajukan_penarikan', $penarikan, null, $penarikan->attributesToArray(), $actor);

            return $penarikan;
        });
    }

    public function setujui(User $actor, PenarikanSaldo $penarikan, ?string $catatan = null): PenarikanSaldo
    {
        $this->assertPetugas($actor, $penarikan);

        return DB::transaction(function () use ($actor, $penarikan, $catatan): PenarikanSaldo {
            $locked = PenarikanSaldo::query()->whereKey($penarikan->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($locked, PenarikanSaldo::STATUS_PENDING);
            $before = $locked->attributesToArray();

            $this->saldo->debit($locked->nasabah, (float) $locked->jumlah, $locked, 'Penarikan saldo #'.$locked->id, $actor);

            $locked->forceFill([
                'status' => PenarikanSaldo::STATUS_APPROVED,
                'diproses_oleh' => $actor->id,
                'diproses_at' => now(),
                'catatan' => $catatan ?? $locked->catatan,
            ])->save();

            $this->audit->log('approve_penarikan', $locked, $before, $locked->attributesToArray(), $actor);

            return $locked;
        });
    }

    public function tolak(User $actor, PenarikanSaldo $penarikan, string $catatan): PenarikanSaldo
    {
        $this->assertPetugas($actor, $penarikan);

        return DB::transaction(function () use ($actor, $penarikan, $catatan): PenarikanSaldo {
            $locked = PenarikanSaldo::query()->whereKey($penarikan->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($locked, PenarikanSaldo::STATUS_PENDING);
            $before = $locked->attributesToArray();

            $locked->forceFill([
                'status' => PenarikanSaldo::STATUS_REJECTED,
                'diproses_oleh' => $actor->id,
                'diproses_at' => now(),
                'catatan' => $catatan,
            ])->save();

            $this->audit->log('reject_penarikan', $locked, $before, $locked->attributesToArray(), $actor);

            return $locked;
        });
    }

    /**
     * Uang sudah diserahkan ke nasabah.
     */
    public function selesaikan(User $actor, PenarikanSaldo $penarikan): PenarikanSaldo
    {
        $this->assertPetugas($actor, $penarikan);

        return DB::transaction(function () use ($actor, $penarikan): PenarikanSaldo {
            $locked = PenarikanSaldo::query()->whereKey($penarikan->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($locked, PenarikanSaldo::STATUS_APPROVED);
            $before = $locked->attributesToArray();

            $locked->forceFill(['status' => PenarikanSaldo::STATUS_COMPLETED, 'diselesaikan_at' => now()])->save();

            $this->audit->log('selesaikan_penarikan', $locked, $before, $locked->attributesToArray(), $actor);

            return $locked;
        });
    }

    private function assertPetugas(User $actor, PenarikanSaldo $penarikan): void
    {
        if (! $actor->isStaff() || ! $actor->canManageBankSampah($penarikan->bank_sampah_id)) {
            throw new AuthorizationException('Anda tidak berhak memproses penarikan ini.');
        }
    }

    private function assertStatus(PenarikanSaldo $penarikan, string $expected): void
    {
        if ($penarikan->status !== $expected) {
            throw ValidationException::withMessages(['status' => ["Penarikan berstatus {$penarikan->status}, tidak dapat diproses."]]);
        }
    }
}
