<?php

namespace App\Http\Resources;

use App\Models\TitikBiopori;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TitikBiopori */
class TitikBioporiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_lokasi' => $this->nama_lokasi ?: ($this->alamat_rt_rw ?: 'Titik Biopori #'.$this->id),
            'deskripsi_lokasi' => $this->deskripsi_lokasi,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'bioporiprint' => (bool) $this->bioporiprint,
            'status' => $this->status,
            'terakhir_diisi_at' => $this->terakhir_diisi_at?->toIso8601String(),
            'estimasi_panen_at' => $this->estimasi_panen_at?->toIso8601String(),
            'status_panen' => $this->statusPanenEfektif(),
        ];
    }
}
