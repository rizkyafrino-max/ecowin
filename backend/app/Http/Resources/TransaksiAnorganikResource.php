<?php

namespace App\Http\Resources;

use App\Models\TransaksiAnorganik;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransaksiAnorganik */
class TransaksiAnorganikResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nasabah_id' => $this->nasabah_id,
            'nasabah' => $this->whenLoaded('nasabah', fn () => $this->nasabah->nama),
            'bank_sampah_id' => $this->bank_sampah_id,
            'jenis_sampah' => $this->whenLoaded('hargaSampah', fn () => $this->hargaSampah?->jenisSampah?->nama_jenis),
            'berat_kg' => (float) $this->berat_kg,
            'harga_per_kg' => (float) $this->harga_per_kg,
            'nilai_rupiah' => (int) $this->nilai_rupiah,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
