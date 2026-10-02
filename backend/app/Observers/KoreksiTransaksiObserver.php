<?php

namespace App\Observers;

use App\Models\KoreksiTransaksi;

class KoreksiTransaksiObserver
{
    public function created(KoreksiTransaksi $koreksi): void
    {
        // The controller records the request together with the transaction's bank scope.
    }

    public function updated(KoreksiTransaksi $koreksi): void
    {
        // Cuma catat kalau statusnya berubah jadi disetujui/ditolak
        // The controller records the decision with the transaction's bank scope.
    }
}
