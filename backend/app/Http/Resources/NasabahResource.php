<?php

namespace App\Http\Resources;

use App\Models\Nasabah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * NIK, path foto KTP/KK, dan token QR TIDAK pernah dikirim di sini.
 *
 * @mixin Nasabah
 */
class NasabahResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_nasabah' => $this->nomor_nasabah,
            'nama' => $this->nama,
            'no_hp' => $this->no_hp,
            'alamat_rt_rw' => $this->alamat_rt_rw,
            'saldo' => (float) $this->saldo,
            'status' => $this->status,
            'status_verifikasi' => $this->status_verifikasi,
            'bank_sampah' => $this->whenLoaded('bankSampah', fn () => [
                'id' => $this->bankSampah->id,
                'nama' => $this->bankSampah->nama_bank_sampah,
                'rt' => $this->bankSampah->rt,
                'rw' => $this->bankSampah->rw,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
