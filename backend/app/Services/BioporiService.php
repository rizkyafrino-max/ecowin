<?php

namespace App\Services;

use App\Models\AktivitasBiopori;
use App\Models\TitikBiopori;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Organik -> Aktivitas Biopori -> BioporiPrint.
 * Nasabah melapor (status pending), petugas memverifikasi (approved/rejected).
 */
class BioporiService
{
    public function __construct(private SecureUploader $uploader, private AuditLogger $audit) {}

    /**
     * @param  array{titik_biopori_id: int, tanggal_pemasukan: string, jenis_sampah: string, berat_kg: float|string, catatan?: string|null}  $data
     */
    public function lapor(User $actor, array $data, UploadedFile $foto): AktivitasBiopori
    {
        $nasabah = $actor->nasabah;

        if (! $actor->isNasabah() || ! $nasabah) {
            throw new AuthorizationException('Hanya nasabah yang dapat melaporkan aktivitas Biopori.');
        }

        $titik = TitikBiopori::query()->findOrFail($data['titik_biopori_id']);

        if ((int) $titik->bank_sampah_id !== (int) $nasabah->bank_sampah_id || $titik->status !== 'aktif') {
            throw ValidationException::withMessages(['titik_biopori_id' => ['Lokasi Biopori tidak tersedia untuk Bank Sampah Anda.']]);
        }

        $metode = $data['metode_pengolahan'] ?? ($titik->bioporiprint ? 'bioporiprint' : 'biopori');
        if ($metode === 'bioporiprint' && ! $titik->bioporiprint) {
            throw ValidationException::withMessages(['metode_pengolahan' => ['Lokasi ini bukan titik BioporiPrint.']]);
        }

        $fotoPath = $this->uploader->storeImage($foto, 'bukti-biopori');

        return DB::transaction(function () use ($actor, $nasabah, $titik, $data, $fotoPath, $metode): AktivitasBiopori {
            $snapshot = [
                'titik_biopori_id' => $titik->id,
                'lokasi' => $titik->nama_lokasi ?? $titik->alamat_rt_rw,
                'tanggal_pemasukan' => $data['tanggal_pemasukan'],
                'metode_pengolahan' => $metode,
                'jenis_sampah' => $data['jenis_sampah'],
                'berat_kg' => (float) $data['berat_kg'],
                'catatan' => $data['catatan'] ?? null,
                'foto_bukti_path' => $fotoPath,
                'dilaporkan_at' => now()->toIso8601String(),
            ];

            $aktivitas = new AktivitasBiopori;
            $aktivitas->forceFill([
                'nasabah_id' => $nasabah->id,
                'bank_sampah_id' => $nasabah->bank_sampah_id,
                'titik_biopori_id' => $titik->id,
                'tanggal_pemasukan' => $data['tanggal_pemasukan'],
                'metode_pengolahan' => $metode,
                'jenis_sampah' => $data['jenis_sampah'],
                'berat_kg' => $data['berat_kg'],
                'deskripsi' => $data['catatan'] ?? null,
                'foto_bukti_path' => $fotoPath,
                'status' => AktivitasBiopori::STATUS_PENDING,
                'data_awal' => $snapshot,
            ])->save();

            $this->audit->log('lapor_biopori', $aktivitas, null, $snapshot, $actor);

            return $aktivitas;
        });
    }

    public function setujui(User $actor, AktivitasBiopori $aktivitas, ?string $catatan = null): AktivitasBiopori
    {
        return $this->putuskan($actor, $aktivitas, AktivitasBiopori::STATUS_APPROVED, $catatan);
    }

    public function tolak(User $actor, AktivitasBiopori $aktivitas, string $catatan): AktivitasBiopori
    {
        return $this->putuskan($actor, $aktivitas, AktivitasBiopori::STATUS_REJECTED, $catatan);
    }

    public function tandaiPanen(User $actor, TitikBiopori $titik, float $hasilKomposKg): TitikBiopori
    {
        if (! $actor->isStaff() || ! $actor->canManageBankSampah($titik->bank_sampah_id)) {
            throw new AuthorizationException('Anda tidak berhak memperbarui lokasi Biopori ini.');
        }

        $before = $titik->attributesToArray();
        $titik->forceFill([
            'status_panen' => TitikBiopori::PANEN_SUDAH,
            'dipanen_at' => now(),
            'hasil_kompos_kg' => $hasilKomposKg,
        ])->save();

        // Aktivitas yang sudah disetujui di lokasi ini selesai begitu lokasinya dipanen.
        AktivitasBiopori::query()->where('titik_biopori_id', $titik->id)->where('status', AktivitasBiopori::STATUS_APPROVED)
            ->update(['status' => AktivitasBiopori::STATUS_COMPLETED]);

        $this->audit->log('panen_biopori', $titik, $before, $titik->attributesToArray(), $actor);

        return $titik;
    }

    private function putuskan(User $actor, AktivitasBiopori $aktivitas, string $status, ?string $catatan): AktivitasBiopori
    {
        if (! $actor->isStaff() || ! $actor->canManageBankSampah($aktivitas->bank_sampah_id)) {
            throw new AuthorizationException('Anda tidak berhak memverifikasi aktivitas ini.');
        }

        return DB::transaction(function () use ($actor, $aktivitas, $status, $catatan): AktivitasBiopori {
            $locked = AktivitasBiopori::query()->whereKey($aktivitas->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['status' => ['Aktivitas ini sudah diverifikasi.']]);
            }

            $before = $locked->attributesToArray();

            $locked->forceFill([
                'status' => $status,
                'catatan_petugas' => $catatan,
                'diperiksa_oleh' => $actor->id,
                'waktu_diperiksa' => now(),
            ])->save();

            if ($status === AktivitasBiopori::STATUS_APPROVED && $locked->titikBiopori) {
                $titik = $locked->titikBiopori;
                $diisi = $locked->tanggal_pemasukan;

                if (! $titik->terakhir_diisi_at || $diisi->greaterThan($titik->terakhir_diisi_at)) {
                    $titik->forceFill([
                        'terakhir_diisi_at' => $diisi,
                        'estimasi_panen_at' => $diisi->copy()->addDays(config('ecowin.biopori.hari_panen')),
                        'status_panen' => TitikBiopori::PANEN_BELUM,
                    ])->save();
                }
            }

            $aksi = $status === AktivitasBiopori::STATUS_APPROVED ? 'approve_biopori' : 'reject_biopori';
            $this->audit->log($aksi, $locked, $before, $locked->attributesToArray(), $actor);

            return $locked;
        });
    }
}
