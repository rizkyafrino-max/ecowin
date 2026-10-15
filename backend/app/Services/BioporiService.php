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

        if ((int) $titik->bank_sampah_id !== (int) $nasabah->bank_sampah_id || $titik->status !== 'aktif' || $titik->verifikasi !== TitikBiopori::VERIFIKASI_APPROVED) {
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

    /** Jumlah maksimum pengajuan titik yang boleh menunggu sekaligus per nasabah. */
    public const MAKS_PENGAJUAN_PENDING = 3;

    /** Titik yang lebih dekat dari ini (meter) dengan titik lain di Bank Sampah yang sama dianggap duplikat. */
    public const JARAK_DUPLIKAT_METER = 15;

    /**
     * Nasabah mengajukan titik Biopori baru. Titik berstatus "pending" dan belum tampil di peta resmi
     * sampai petugas Bank Sampah yang sama menyetujuinya.
     *
     * @param  array{nama_lokasi: string, latitude: float|string, longitude: float|string, deskripsi_lokasi?: string|null, alamat_rt_rw?: string|null}  $data
     */
    public function ajukanTitik(User $actor, array $data): TitikBiopori
    {
        $nasabah = $actor->nasabah;

        if (! $actor->isNasabah() || ! $nasabah || ! $actor->isActive()) {
            throw new AuthorizationException('Hanya nasabah aktif yang dapat mengajukan titik Biopori.');
        }

        if ($nasabah->status_verifikasi !== 'verified') {
            throw ValidationException::withMessages(['nama_lokasi' => ['Akun Anda belum diverifikasi petugas, sehingga belum bisa mengajukan titik baru.']]);
        }

        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];

        return DB::transaction(function () use ($actor, $nasabah, $data, $lat, $lng): TitikBiopori {
            // Kunci baris nasabah agar batas pengajuan tidak bisa dilewati dengan permintaan serentak.
            $nasabah->newQuery()->whereKey($nasabah->id)->lockForUpdate()->first();

            $menunggu = TitikBiopori::query()->where('diajukan_oleh', $actor->id)->where('verifikasi', TitikBiopori::VERIFIKASI_PENDING)->count();
            if ($menunggu >= self::MAKS_PENGAJUAN_PENDING) {
                throw ValidationException::withMessages(['nama_lokasi' => ['Anda sudah memiliki '.self::MAKS_PENGAJUAN_PENDING.' pengajuan yang menunggu verifikasi. Tunggu keputusan petugas dulu.']]);
            }

            $dekat = TitikBiopori::query()
                ->where('bank_sampah_id', $nasabah->bank_sampah_id)
                ->whereIn('verifikasi', [TitikBiopori::VERIFIKASI_APPROVED, TitikBiopori::VERIFIKASI_PENDING])
                ->whereNotNull('latitude')->whereNotNull('longitude')
                ->get(['id', 'nama_lokasi', 'latitude', 'longitude'])
                ->first(fn (TitikBiopori $t): bool => $this->jarakMeter($lat, $lng, (float) $t->latitude, (float) $t->longitude) < self::JARAK_DUPLIKAT_METER);
            if ($dekat) {
                throw ValidationException::withMessages(['latitude' => ['Sudah ada titik "'.$dekat->nama_lokasi.'" di lokasi ini. Pilih lokasi yang berbeda.']]);
            }

            $titik = new TitikBiopori([
                'nasabah_id' => $nasabah->id,
                'nama_lokasi' => trim($data['nama_lokasi']),
                'alamat_rt_rw' => trim((string) ($data['alamat_rt_rw'] ?? '')) ?: ($nasabah->alamat_rt_rw ?: '-'),
                'deskripsi_lokasi' => ($data['deskripsi_lokasi'] ?? null) ?: null,
                'latitude' => round($lat, 7),
                'longitude' => round($lng, 7),
                'tanggal_tanam' => now()->toDateString(),
                'jumlah_pipa' => 1,
                'bioporiprint' => true,
                'status' => 'aktif',
            ]);
            $titik->forceFill([
                'bank_sampah_id' => $nasabah->bank_sampah_id,
                'dicatat_oleh' => $actor->id,
                'diajukan_oleh' => $actor->id,
                'verifikasi' => TitikBiopori::VERIFIKASI_PENDING,
            ])->save();

            $this->audit->log('ajukan_titik_biopori', $titik, null, $titik->attributesToArray(), $actor);

            return $titik;
        });
    }

    public function setujuiTitik(User $actor, TitikBiopori $titik, ?string $catatan = null): TitikBiopori
    {
        return $this->putuskanTitik($actor, $titik, TitikBiopori::VERIFIKASI_APPROVED, $catatan);
    }

    public function tolakTitik(User $actor, TitikBiopori $titik, string $catatan): TitikBiopori
    {
        return $this->putuskanTitik($actor, $titik, TitikBiopori::VERIFIKASI_REJECTED, $catatan);
    }

    private function putuskanTitik(User $actor, TitikBiopori $titik, string $status, ?string $catatan): TitikBiopori
    {
        if (! $actor->isStaff() || ! $actor->isActive() || ! $actor->canManageBankSampah($titik->bank_sampah_id)) {
            throw new AuthorizationException('Anda tidak berhak memverifikasi titik ini.');
        }

        return DB::transaction(function () use ($actor, $titik, $status, $catatan): TitikBiopori {
            $locked = TitikBiopori::query()->whereKey($titik->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPendingVerifikasi()) {
                throw ValidationException::withMessages(['verifikasi' => ['Titik ini sudah diverifikasi.']]);
            }

            $before = $locked->attributesToArray();
            $locked->forceFill([
                'verifikasi' => $status,
                'catatan_verifikasi' => $catatan,
                'diverifikasi_oleh' => $actor->id,
                'diverifikasi_at' => now(),
            ])->save();

            $aksi = $status === TitikBiopori::VERIFIKASI_APPROVED ? 'setujui_titik_biopori' : 'tolak_titik_biopori';
            $this->audit->log($aksi, $locked, $before, $locked->attributesToArray(), $actor);

            return $locked;
        });
    }

    /** Jarak antar dua koordinat (haversine), dalam meter. */
    private function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
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
