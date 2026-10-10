<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAktivitasBioporiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isNasabah();
    }

    /**
     * Foto bukti wajib. Data tidak langsung valid (status pending).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxDimensi = config('ecowin.upload.foto_max_dimensi');

        return [
            'metode_pengolahan' => ['nullable', 'string', 'in:biopori,bioporiprint,lainnya'],
            'titik_biopori_id' => ['required', 'integer', 'exists:titik_biopori,id'],
            'tanggal_pemasukan' => ['required', 'date', 'before_or_equal:now', 'after:-30 days'],
            'jenis_sampah' => ['required', 'string', 'max:100'],
            'berat_kg' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'foto_bukti' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('ecowin.upload.foto_max_kb'), "dimensions:max_width={$maxDimensi},max_height={$maxDimensi}"],
        ];
    }
}
