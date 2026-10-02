<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Nasabah;

class NasabahObserver
{
    public function created(Nasabah $nasabah): void
    {
        AuditLog::create([
            'user_id' => $nasabah->dibuat_oleh,
            'bank_sampah_id' => $nasabah->bank_sampah_id,
            'aksi' => 'buat_akun_nasabah',
            'detail' => json_encode([
                'nasabah_id' => $nasabah->id,
                'nama' => $nasabah->nama,
                'no_hp' => $nasabah->no_hp,
            ]),
        ]);
    }
}
