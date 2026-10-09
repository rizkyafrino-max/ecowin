<?php

namespace App\Http\Requests;

use App\Models\TransaksiOrganik;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransaksiOrganikRequest extends FormRequest
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
            'jenis_organik' => ['required', 'string', 'max:100'],
            'berat_kg' => ['required', 'numeric', 'gt:0', 'max:10000', 'decimal:0,2'],
            'tanggal' => ['nullable', 'date', 'before_or_equal:today'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'metode_pengolahan' => ['nullable', Rule::in(array_keys(TransaksiOrganik::METODE))],
            'checklist_bebas_plastik' => ['required', 'accepted'],
            'checklist_bebas_logam' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'checklist_bebas_plastik.accepted' => 'Sampah organik wajib bebas plastik.',
            'checklist_bebas_logam.accepted' => 'Sampah organik wajib bebas logam.',
        ];
    }
}
