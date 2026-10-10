<?php

namespace App\Services;

use App\Models\HargaSampah;
use App\Models\JenisSampah;
use Illuminate\Validation\ValidationException;

/**
 * Menentukan harga yang SEDANG BERLAKU dari database berdasarkan jenis sampah,
 * kondisi, dan rentang berat (harga bertingkat). Klien tidak pernah memilih harga.
 *
 * Batas atas inklusif: tingkatan 0–5 kg, 5–10 kg, ≥10 kg berarti 5 kg memakai
 * tingkat pertama dan 10 kg memakai tingkat kedua.
 */
class PriceResolver
{
    public function resolve(JenisSampah $jenis, float $beratKg, ?string $kondisi = null): HargaSampah
    {
        if ($jenis->status !== 'aktif') {
            throw ValidationException::withMessages(['jenis_sampah_id' => ['Jenis sampah tidak aktif.']]);
        }

        if ($jenis->kategori?->tipe !== 'anorganik') {
            throw ValidationException::withMessages(['jenis_sampah_id' => ['Jenis sampah bukan kategori anorganik.']]);
        }

        $harga = HargaSampah::query()
            ->where('jenis_sampah_id', $jenis->id)
            ->when($kondisi, fn ($q) => $q->where('kondisi', $kondisi))
            ->berlaku()
            ->untukBerat($beratKg)
            // Versi harga terbaru menang; dalam satu versi, batas atas inklusif.
            ->orderByDesc('berlaku_mulai')
            ->orderBy('minimal_berat')
            ->orderByDesc('id')
            ->first();

        if (! $harga) {
            throw ValidationException::withMessages(['jenis_sampah_id' => ['Belum ada harga aktif untuk jenis sampah dan berat ini.']]);
        }

        return $harga;
    }

    public function hitungNilai(float $beratKg, HargaSampah $harga): int
    {
        return (int) round($beratKg * (float) $harga->harga_per_kg);
    }
}
