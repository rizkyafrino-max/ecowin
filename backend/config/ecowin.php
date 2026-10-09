<?php

return [

    /*
    | Masa berlaku token API aplikasi Android (menit).
    | Access token pendek, refresh token panjang dan dirotasi setiap dipakai.
    */
    'tokens' => [
        'access_ttl' => (int) env('ECOWIN_ACCESS_TOKEN_TTL', 60 * 24),          // 1 hari
        'refresh_ttl' => (int) env('ECOWIN_REFRESH_TOKEN_TTL', 60 * 24 * 60),   // 60 hari
    ],

    /*
    | Estimasi hasil kompos = berat bahan organik (kg) x rasio.
    | Dapat dikonfigurasi per metode pengolahan.
    */
    'kompos' => [
        'rasio_default' => (float) env('ECOWIN_KOMPOS_RASIO', 0.5),
        'rasio_per_metode' => [
            'komposter' => (float) env('ECOWIN_KOMPOS_RASIO_KOMPOSTER', 0.5),
            'biopori' => (float) env('ECOWIN_KOMPOS_RASIO_BIOPORI', 0.4),
            'takakura' => (float) env('ECOWIN_KOMPOS_RASIO_TAKAKURA', 0.45),
            'maggot' => (float) env('ECOWIN_KOMPOS_RASIO_MAGGOT', 0.3),
        ],
    ],

    /*
    | Lubang biopori dianggap siap panen N hari setelah terakhir diisi.
    */
    'biopori' => [
        'hari_panen' => (int) env('ECOWIN_BIOPORI_HARI_PANEN', 60),
    ],

    'upload' => [
        // Ukuran maksimal foto bukti (KB) dan dimensi maksimal (px).
        'foto_max_kb' => (int) env('ECOWIN_FOTO_MAX_KB', 5120),
        'foto_max_dimensi' => (int) env('ECOWIN_FOTO_MAX_DIMENSI', 6000),
        // Foto bukti & dokumen identitas disimpan di disk PRIVAT.
        'disk' => env('ECOWIN_UPLOAD_DISK', 'local'),
    ],

    'penarikan' => [
        'minimal' => (int) env('ECOWIN_PENARIKAN_MINIMAL', 1000),
    ],

    /*
    | Alamat aplikasi web Nasabah (React). Setelah login Google di halaman Laravel,
    | Nasabah diarahkan ke sini membawa kode sekali pakai (diikat PKCE, berlaku 60 detik).
    | URL ini tetap (dari konfigurasi), tidak pernah diambil dari input pengguna.
    */
    'web_url' => rtrim((string) env('ECOWIN_WEB_URL', 'http://localhost:5173'), '/'),
    'web_login_code_ttl' => 60,
];
