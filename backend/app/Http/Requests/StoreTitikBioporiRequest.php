<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengajuan titik Biopori oleh nasabah. Koordinat dibatasi ke wilayah Indonesia untuk menangkap salah ketik.
 * Bank Sampah selalu diambil dari akun nasabah (tidak dari input).
 */
class StoreTitikBioporiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isNasabah();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama_lokasi' => ['required', 'string', 'min:3', 'max:100'],
            'deskripsi_lokasi' => ['nullable', 'string', 'max:255'],
            'alamat_rt_rw' => ['nullable', 'string', 'max:150'],
            'latitude' => ['required', 'numeric', 'between:-11.5,6.5'],
            'longitude' => ['required', 'numeric', 'between:94,141.5'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'Koordinat di luar wilayah Indonesia. Periksa kembali lokasi Anda.',
            'longitude.between' => 'Koordinat di luar wilayah Indonesia. Periksa kembali lokasi Anda.',
            'nama_lokasi.min' => 'Nama lokasi minimal 3 karakter.',
        ];
    }
}
