<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\KoreksiTransaksi;
use Illuminate\Support\Facades\Auth;

class KoreksiTransaksiObserver
{
    public function created(KoreksiTransaksi $koreksi): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'ajukan_koreksi_transaksi',
            'detail' => json_encode([
                'transaksi_id' => $koreksi->transaksi_id,
                'tipe_transaksi' => $koreksi->tipe_transaksi,
                'alasan' => $koreksi->alasan,
            ]),
        ]);
    }

    public function updated(KoreksiTransaksi $koreksi): void
    {
        // Cuma catat kalau statusnya berubah jadi disetujui/ditolak
        if ($koreksi->isDirty('status') && $koreksi->status !== 'menunggu') {
            AuditLog::create([
                'user_id' => Auth::id(),
                'aksi' => 'koreksi_transaksi_' . $koreksi->status,
                'detail' => json_encode([
                    'koreksi_id' => $koreksi->id,
                    'disetujui_oleh' => $koreksi->disetujui_oleh,
                ]),
            ]);
        }
    }
}