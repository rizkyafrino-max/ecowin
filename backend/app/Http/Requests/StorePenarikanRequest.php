<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePenarikanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * nasabah_id hanya dipakai bila petugas/admin yang mengajukan; untuk nasabah selalu diambil dari akun login.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nasabah_id' => [$this->user()?->isNasabah() ? 'prohibited' : 'required', 'integer', 'exists:nasabah,id'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100000000'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
