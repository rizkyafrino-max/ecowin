<?php

/**
 * Bootstrap tes: memuat autoload Composer dan menyediakan opsi_openssl() untuk tes yang membuat kunci RSA.
 *
 * PHP di Windows (mis. Laragon) sering tidak menemukan openssl.cnf sehingga openssl_pkey_new() gagal ("no such file") dan
 * tes yang membuat kunci (GoogleIdTokenVerifierTest, PushFcmTest) ikut gagal. Mengatur OPENSSL_CONF lewat putenv() saat
 * berjalan tidak berpengaruh, jadi berkasnya diberikan langsung lewat opsi "config". Di mesin yang sudah punya konfigurasi
 * (mis. Linux/CI) fungsi ini mengembalikan array kosong sehingga perilakunya tidak berubah.
 */
require __DIR__.'/../vendor/autoload.php';

if (! function_exists('opsi_openssl')) {
    /** @return array{config?: string} */
    function opsi_openssl(): array
    {
        $env = getenv('OPENSSL_CONF');
        if ($env !== false && $env !== '' && is_file($env)) {
            return [];
        }

        foreach ([dirname(PHP_BINARY).'/extras/ssl/openssl.cnf', '/etc/ssl/openssl.cnf', '/usr/lib/ssl/openssl.cnf'] as $berkas) {
            if (is_file($berkas)) {
                return ['config' => $berkas];
            }
        }

        return [];
    }
}
