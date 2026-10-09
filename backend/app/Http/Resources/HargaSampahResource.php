<?php

namespace App\Http\Resources;

use App\Models\HargaSampah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HargaSampah */
class HargaSampahResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jenis_sampah_id' => $this->jenis_sampah_id,
            'jenis_sampah' => $this->whenLoaded('jenisSampah', fn () => $this->jenisSampah->nama_jenis),
            'kondisi' => $this->kondisi,
            'minimal_berat' => (float) $this->minimal_berat,
            'maksimal_berat' => $this->maksimal_berat !== null ? (float) $this->maksimal_berat : null,
            'harga_per_kg' => (int) $this->harga_per_kg,
            'berlaku_mulai' => $this->berlaku_mulai?->toIso8601String(),
            'berlaku_sampai' => $this->berlaku_sampai?->toIso8601String(),
            'status' => $this->status,
        ];
    }
}
