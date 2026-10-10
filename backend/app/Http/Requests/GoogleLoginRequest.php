<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Hanya ID token Google yang diterima. Email, google_id, atau role dari klien diabaikan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string', 'min:100', 'max:4096'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
