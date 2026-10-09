<?php

namespace App\Support;

/**
 * Nomor HP Indonesia. Disimpan dalam bentuk lokal baku (08xxxxxxxxxx) agar pengecekan unik
 * konsisten.
 */
final class Phone
{
    /** Bentuk lokal baku, atau null bila bukan nomor seluler Indonesia yang valid. */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        return preg_match('/^08[0-9]{7,12}$/', $digits) === 1 ? $digits : null;
    }

    /** Format internasional tanpa "+", mis. 6281234567890. */
    public static function international(string $local): string
    {
        return '62'.substr($local, 1);
    }

    /** Samarkan untuk ditampilkan, mis. 0812••••7890. */
    public static function mask(string $local): string
    {
        return substr($local, 0, 4).str_repeat('•', max(0, strlen($local) - 8)).substr($local, -4);
    }
}
