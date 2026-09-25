<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\PenarikanSaldo;
use Illuminate\Support\Facades\Auth;

class PenarikanSaldoObserver
{
    public function updated(PenarikanSaldo $penarikan): void
    {
        if ($penarikan->isDirty('status') && $penarikan->status === 'selesai') {
            AuditLog::create([
                'user_id' => Auth::id(),
                'aksi' => 'proses_penarikan_saldo',
                'detail' => json_encode([
                    'penarikan_id' => $penarikan->id,
                    'nasabah_id' => $penarikan->nasabah_id,
                    'jumlah' => $penarikan->jumlah,
                    'diproses_oleh' => $penarikan->diproses_oleh,
                ]),
            ]);
        }
    }
}