<?php

namespace App\Http\Resources;

use App\Models\PenarikanSaldo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PenarikanSaldo */
class PenarikanSaldoResource extends JsonResource
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
            'jumlah' => (int) $this->jumlah,
            'status' => $this->status,
            'catatan' => $this->catatan,
            'diproses_at' => $this->diproses_at?->toIso8601String(),
            'diselesaikan_at' => $this->diselesaikan_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
