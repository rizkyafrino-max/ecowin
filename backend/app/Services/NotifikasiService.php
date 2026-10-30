<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Membuat notifikasi dalam aplikasi untuk nasabah. Kegagalan membuat notifikasi tidak boleh menggagalkan
 * proses utama (penarikan, setoran, dst.), jadi dilaporkan lalu diabaikan.
 */
class NotifikasiService
{
    public function kirim(?User $penerima, string $tipe, string $judul, string $isi, ?string $aksi = null): void
    {
        if (! $penerima || ! $penerima->isNasabah()) {
            return;
        }

        try {
            $n = new Notifikasi;
            $n->forceFill([
                'user_id' => $penerima->id,
                'tipe' => Str::limit($tipe, 40, ''),
                'judul' => Str::limit($judul, 120, ''),
                'isi' => Str::limit($isi, 300, '…'),
                'aksi' => $aksi,
            ])->save();
            // "Ping" tanpa isi ke perangkat nasabah; dijalankan setelah transaksi pemanggil selesai.
            DB::afterCommit(fn () => app(PushService::class)->bangunkan([$penerima->id]));
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function rupiah(float|int|string $v): string
    {
        return 'Rp '.number_format((float) $v, 0, ',', '.');
    }
}
