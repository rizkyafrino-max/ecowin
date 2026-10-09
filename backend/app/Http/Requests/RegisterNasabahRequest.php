<?php

namespace App\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterNasabahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['no_hp' => Phone::normalize($this->input('no_hp')) ?? $this->input('no_hp')]);
    }

    /**
     * Identitas (email, google_id) hanya dari ID token Google yang diverifikasi server; role selalu nasabah.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string', 'min:100', 'max:4096'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'nama' => ['required', 'string', 'min:3', 'max:150'],
            'no_hp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/', Rule::unique('nasabah', 'no_hp')],
            'alamat_rt_rw' => ['required', 'string', 'min:5', 'max:255'],
            'bank_sampah_id' => ['required', 'integer', Rule::exists('bank_sampah', 'id')->where('status', 'aktif')],
            'setuju' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_hp.regex' => 'Nomor HP tidak valid (contoh 081234567890).',
            'no_hp.unique' => 'Nomor HP sudah terdaftar.',
            'bank_sampah_id.exists' => 'Pilih Bank Sampah yang tersedia.',
            'setuju.accepted' => 'Anda harus menyetujui ketentuan.',
        ];
    }
}