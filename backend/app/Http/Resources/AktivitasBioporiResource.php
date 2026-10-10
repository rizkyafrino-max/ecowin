<?php

namespace App\Http\Resources;

use App\Models\AktivitasBiopori;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AktivitasBiopori */
class AktivitasBioporiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nasabah_id' => $this->nasabah_id,
            'titik_biopori_id' => $this->titik_biopori_id,
            'lokasi' => $this->whenLoaded('titikBiopori', fn () => $this->titikBiopori?->nama_lokasi ?: ($this->titikBiopori?->alamat_rt_rw ?: 'Titik Biopori #'.$this->titik_biopori_id)),
            'bioporiprint' => $this->whenLoaded('titikBiopori', fn () => (bool) $this->titikBiopori?->bioporiprint),
            'metode_pengolahan' => $this->metode_pengolahan,
            'metode' => AktivitasBiopori::METODE[$this->metode_pengolahan] ?? $this->metode_pengolahan,
            'tanggal_pemasukan' => $this->tanggal_pemasukan?->toIso8601String(),
            'jenis_sampah' => $this->jenis_sampah,
            'berat_kg' => (float) $this->berat_kg,
            'catatan' => $this->deskripsi,
            'status' => $this->status,
            'catatan_petugas' => $this->catatan_petugas,
            'waktu_diperiksa' => $this->waktu_diperiksa?->toIso8601String(),
            'foto_url' => route('api.biopori.foto', $this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
