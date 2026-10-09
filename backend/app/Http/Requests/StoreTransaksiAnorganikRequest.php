<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransaksiAnorganikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isStaff();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nasabah_id' => ['required', 'integer', 'exists:nasabah,id'],
            'jenis_sampah_id' => ['required', 'integer', 'exists:jenis_sampah,id'],
            'kondisi' => ['nullable', 'string', 'max:50'],
            'berat_kg' => ['required', 'numeric', 'gt:0', 'max:10000', 'decimal:0,2'],
            'foto_dokumentasi' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('ecowin.upload.foto_max_kb')],
        ];
    }
}
