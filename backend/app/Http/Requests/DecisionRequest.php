<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isStaff();
    }

    /**
     * Catatan wajib saat menolak.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'catatan' => [$this->routeIs('*.reject') ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }
}
