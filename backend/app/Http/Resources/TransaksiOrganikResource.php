<?php

namespace App\Http\Resources;

use App\Models\TransaksiOrganik;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransaksiOrganik */
class TransaksiOrganikResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nasabah_id' => $this->nasabah_id,
            'bank_sampah_id' => $this->bank_sampah_id,
            'tanggal' => $this->tanggal?->toDateString() ?? $this->created_at?->toDateString(),
            'jenis_organik' => $this->jenis_organik,
            'lokasi' => $this->lokasi,
            'metode_pengolahan' => $this->metode_pengolahan,
            'berat_kg' => (float) $this->berat_kg,
            'estimasi_kompos_kg' => (float) $this->estimasi_kompos_kg,
            'status_pengolahan' => $this->status_pengolahan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
