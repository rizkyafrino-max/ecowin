<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\PenarikanSaldo;
use Illuminate\Support\Facades\Auth;

class PenarikanSaldoObserver
{
    public function updated(PenarikanSaldo $penarikan): void
    {
        if ($penarikan->wasChanged('status') && in_array($penarikan->status, ['selesai', 'ditolak'], true) && Auth::id()) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'bank_sampah_id' => $penarikan->bank_sampah_id,
                'aksi' => $penarikan->status === 'selesai' ? 'proses_penarikan_saldo' : 'tolak_penarikan_saldo',
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
