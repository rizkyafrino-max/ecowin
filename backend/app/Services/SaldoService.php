<?php

namespace App\Services;

use App\Models\MutasiSaldo;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya jalur untuk mengubah saldo nasabah. Setiap perubahan:
 * - dikunci per baris (lockForUpdate) untuk mencegah race condition,
 * - dicatat ke mutasi_saldo (sumber, sebelum, sesudah),
 * - dicatat ke audit log.
 */
class SaldoService
{
    public function __construct(private AuditLogger $audit) {}

    public function kredit(Nasabah $nasabah, float $jumlah, Model $sumber, string $keterangan, ?User $actor = null): MutasiSaldo
    {
        return $this->mutasi($nasabah, MutasiSaldo::KREDIT, $jumlah, $sumber, $keterangan, $actor);
    }

    public function debit(Nasabah $nasabah, float $jumlah, Model $sumber, string $keterangan, ?User $actor = null): MutasiSaldo
    {
        return $this->mutasi($nasabah, MutasiSaldo::DEBIT, $jumlah, $sumber, $keterangan, $actor);
    }

    private function mutasi(Nasabah $nasabah, string $tipe, float $jumlah, Model $sumber, string $keterangan, ?User $actor): MutasiSaldo
    {
        if ($jumlah <= 0) {
            throw ValidationException::withMessages(['jumlah' => ['Jumlah mutasi saldo harus lebih dari 0.']]);
        }

        return DB::transaction(function () use ($nasabah, $tipe, $jumlah, $sumber, $keterangan, $actor): MutasiSaldo {
            /** @var Nasabah $locked */
            $locked = Nasabah::query()->whereKey($nasabah->getKey())->lockForUpdate()->firstOrFail();

            $sebelum = round((float) $locked->saldo, 2);
            $sesudah = round($tipe === MutasiSaldo::KREDIT ? $sebelum + $jumlah : $sebelum - $jumlah, 2);

            if ($sesudah < 0) {
                throw ValidationException::withMessages(['jumlah' => ['Saldo nasabah tidak mencukupi.']]);
            }

            $locked->forceFill(['saldo' => $sesudah])->save();

            $mutasi = new MutasiSaldo;
            $mutasi->forceFill([
                'nasabah_id' => $locked->id,
                'bank_sampah_id' => $locked->bank_sampah_id,
                'tipe' => $tipe,
                'jumlah' => $jumlah,
                'saldo_sebelum' => $sebelum,
                'saldo_sesudah' => $sesudah,
                'sumber_type' => $sumber::class,
                'sumber_id' => $sumber->getKey(),
                'keterangan' => $keterangan,
                'dibuat_oleh' => $actor?->id,
            ])->save();

            $this->audit->log('perubahan_saldo', $locked, ['saldo' => $sebelum], ['saldo' => $sesudah, 'tipe' => $tipe, 'jumlah' => $jumlah, 'sumber' => class_basename($sumber).'#'.$sumber->getKey()], $actor);

            $nasabah->setRawAttributes($locked->getAttributes(), true);

            return $mutasi;
        });
    }
}
