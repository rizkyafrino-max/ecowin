<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Nasabah;
use Illuminate\Support\Facades\Auth;

class NasabahObserver
{
    public function created(Nasabah $nasabah): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'buat_akun_nasabah',
            'detail' => json_encode([
                'nasabah_id' => $nasabah->id,
                'nama' => $nasabah->nama,
                'no_hp' => $nasabah->no_hp,
            ]),
        ]);
    }
}