<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHargaSampahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis_sampah_id' => ['required', 'integer', 'exists:jenis_sampah,id'],
            'kondisi' => ['nullable', 'string', 'max:50'],
            'minimal_berat' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'maksimal_berat' => ['nullable', 'numeric', 'gt:minimal_berat', 'max:100000'],
            'harga_per_kg' => ['required', 'integer', 'min:0', 'max:10000000'],
            'berlaku_mulai' => ['nullable', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after:berlaku_mulai'],
        ];
    }
}
