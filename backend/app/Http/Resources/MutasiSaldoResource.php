<?php

namespace App\Http\Resources;

use App\Models\MutasiSaldo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MutasiSaldo */
class MutasiSaldoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipe' => $this->tipe,
            'jumlah' => (float) $this->jumlah,
            'saldo_sebelum' => (float) $this->saldo_sebelum,
            'saldo_sesudah' => (float) $this->saldo_sesudah,
            'keterangan' => $this->keterangan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
