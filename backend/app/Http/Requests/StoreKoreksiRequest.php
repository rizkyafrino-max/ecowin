<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKoreksiRequest extends FormRequest
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
            'tipe_transaksi' => ['required', 'in:anorganik,organik'],
            'transaksi_id' => ['required', 'integer', 'min:1'],
            'berat_kg' => ['required', 'numeric', 'gt:0', 'max:10000', 'decimal:0,2'],
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
