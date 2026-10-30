<?php

namespace App\Support;

/**
 * Format angka desimal gaya Indonesia: koma sebagai pemisah desimal ("0,5").
 * Di layar pengguna mengetik/membaca koma; di server dan database selalu titik.
 */
final class Desimal
{
    /** Nilai dari input (koma atau titik) menjadi float; null bila kosong/tidak valid. */
    public static function parse(mixed $nilai): ?float
    {
        $s = self::simpan($nilai);

        return $s !== null && is_numeric($s) ? (float) $s : null;
    }

    /** String bertitik untuk disimpan ("0,5" → "0.5"); null bila kosong. */
    public static function simpan(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $s = str_replace(',', '.', trim((string) $nilai));

        return $s === '' ? null : $s;
    }

    /** Nilai dari database untuk ditampilkan di input ("12.50" → "12,50"). */
    public static function tampil(mixed $nilai): ?string
    {
        return $nilai === null || $nilai === '' ? null : str_replace('.', ',', (string) $nilai);
    }

    /** Apakah teks berformat angka desimal valid dengan maksimal $desimal angka di belakang koma. */
    public static function valid(mixed $nilai, int $desimal = 2): bool
    {
        $s = self::simpan($nilai);

        return $s !== null && preg_match('/^\d{1,10}(\.\d{1,'.max(1, $desimal).'})?$/', $s) === 1;
    }
}
