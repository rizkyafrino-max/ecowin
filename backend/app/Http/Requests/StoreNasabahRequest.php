<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNasabahRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:191', 'unique:users,email'],
            'no_hp' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/', 'unique:nasabah,no_hp'],
            'alamat_rt_rw' => ['required', 'string', 'max:255'],
            'nisn_atau_nik' => ['nullable', 'digits_between:10,16'],
            'bank_sampah_id' => [$this->user()?->isAdmin() ? 'required' : 'prohibited', 'integer', 'exists:bank_sampah,id'],
        ];
    }
}
