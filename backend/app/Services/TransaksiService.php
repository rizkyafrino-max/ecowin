<?php

namespace App\Services;

use App\Models\JenisSampah;
use App\Models\Nasabah;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransaksiService
{
    public function __construct(
        private PriceResolver $prices,
        private SaldoService $saldo,
        private KomposCalculator $kompos,
        private AuditLogger $audit,
        private SecureUploader $uploader,
    ) {}

    /**
     * Setoran anorganik: harga diambil dari DB (bertingkat), total = berat x harga/kg,
     * saldo bertambah dan tercatat di mutasi_saldo dalam satu transaksi database.
     */
    public function catatAnorganik(User $actor, Nasabah $nasabah, JenisSampah $jenis, float $beratKg, ?string $kondisi = null, ?UploadedFile $foto = null): TransaksiAnorganik
    {
        $this->assertBolehMencatat($actor, $nasabah);

        $harga = $this->prices->resolve($jenis, $beratKg, $kondisi);
        $nilai = $this->prices->hitungNilai($beratKg, $harga);
        $fotoPath = $foto ? $this->uploader->storeImage($foto, 'dokumentasi-anorganik') : null;

        return DB::transaction(function () use ($actor, $nasabah, $harga, $beratKg, $nilai, $fotoPath): TransaksiAnorganik {
            $transaksi = new TransaksiAnorganik;
            $transaksi->forceFill([
                'nasabah_id' => $nasabah->id,
                'bank_sampah_id' => $nasabah->bank_sampah_id,
                'harga_sampah_id' => $harga->id,
                'berat_kg' => $beratKg,
                'harga_per_kg' => $harga->harga_per_kg,
                'nilai_rupiah' => $nilai,
                'dicatat_oleh' => $actor->id,
                'foto_dokumentasi_path' => $fotoPath,
            ])->save();

            if ($nilai > 0) {
                $this->saldo->kredit($nasabah, $nilai, $transaksi, 'Setoran anorganik #'.$transaksi->id, $actor);
            }

            $this->audit->log('buat_transaksi_anorganik', $transaksi, null, $transaksi->attributesToArray(), $actor);

            return $transaksi;
        });
    }

    /**
     * Setoran organik: TIDAK menambah saldo rupiah. Estimasi kompos dari rasio konfigurasi.
     *
     * @param  array{jenis_organik: string, berat_kg: float|string, metode_pengolahan?: string|null, lokasi?: string|null, tanggal?: string|null}  $data
     */
    public function catatOrganik(User $actor, Nasabah $nasabah, array $data): TransaksiOrganik
    {
        $this->assertBolehMencatat($actor, $nasabah);

        $berat = (float) $data['berat_kg'];
        $metode = $data['metode_pengolahan'] ?? 'komposter';

        return DB::transaction(function () use ($actor, $nasabah, $data, $berat, $metode): TransaksiOrganik {
            $transaksi = new TransaksiOrganik;
            $transaksi->forceFill([
                'nasabah_id' => $nasabah->id,
                'bank_sampah_id' => $nasabah->bank_sampah_id,
                'tanggal' => $data['tanggal'] ?? now()->toDateString(),
                'jenis_organik' => $data['jenis_organik'],
                'lokasi' => $data['lokasi'] ?? null,
                'metode_pengolahan' => $metode,
                'berat_kg' => $berat,
                'checklist_bebas_plastik' => true,
                'checklist_bebas_logam' => true,
                'estimasi_kompos_kg' => $this->kompos->estimasi($berat, $metode),
                'status_pengolahan' => 'diproses',
                'dicatat_oleh' => $actor->id,
            ])->save();

            $this->audit->log('buat_transaksi_organik', $transaksi, null, $transaksi->attributesToArray(), $actor);

            return $transaksi;
        });
    }

    private function assertBolehMencatat(User $actor, Nasabah $nasabah): void
    {
        if (! $actor->isStaff() || ! $actor->canManageBankSampah($nasabah->bank_sampah_id)) {
            throw new AuthorizationException('Anda tidak berhak mencatat transaksi untuk nasabah ini.');
        }

        if (! $nasabah->isActive()) {
            throw ValidationException::withMessages(['nasabah_id' => ['Nasabah tidak aktif.']]);
        }
    }
}
