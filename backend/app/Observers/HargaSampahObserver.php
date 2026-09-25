<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\HargaSampah;
use Illuminate\Support\Facades\Auth;

class HargaSampahObserver
{
    /**
     * Handle the HargaSampah "created" event.
     */
    public function created(HargaSampah $hargaSampah): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'ubah_harga',
            'detail' => json_encode([
                'jenis_sampah_id' => $hargaSampah->jenis_sampah_id,
                'kondisi' => $hargaSampah->kondisi,
                'harga_per_kg' => $hargaSampah->harga_per_kg,
                'berlaku_mulai' => $hargaSampah->berlaku_mulai,
            ]),
        ]);
    }
}