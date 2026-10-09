<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Hanya data profil biasa. role, email, bank_sampah_id, saldo, status DILARANG.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $nasabahId = $this->user()?->nasabah?->id;

        return [
            'nama' => ['sometimes', 'string', 'max:150'],
            'no_hp' => ['sometimes', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/', Rule::unique('nasabah', 'no_hp')->ignore($nasabahId)],
            'alamat_rt_rw' => ['sometimes', 'string', 'max:255'],
            'role' => ['prohibited'],
            'email' => ['prohibited'],
            'bank_sampah_id' => ['prohibited'],
            'saldo' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
