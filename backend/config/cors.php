<?php

/*
| CORS untuk React Web Nasabah (SPA di origin berbeda). Hanya origin yang didaftarkan
| di ECOWIN_WEB_ORIGINS (pisahkan koma) yang boleh memanggil API.
| Autentikasi memakai header Authorization: Bearer, bukan cookie, sehingga
| supports_credentials dimatikan.
*/

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('ECOWIN_WEB_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173'))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => false,
];
