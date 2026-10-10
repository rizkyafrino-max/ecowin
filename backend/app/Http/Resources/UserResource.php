<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'bank_sampah_id' => $this->bank_sampah_id,
            'nasabah' => $this->when($this->isNasabah(), fn () => new NasabahResource($this->nasabah?->loadMissing('bankSampah'))),
        ];
    }
}
