<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_user_id' => ['required', 'integer', 'min:1'],
            'work_code' => ['required', 'string', 'max:64'],
            'stars' => ['required', 'integer', 'between:1,5'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider_user_id.required' => 'El proveedor es obligatorio.',
            'provider_user_id.integer' => 'El proveedor debe ser un entero.',
            'work_code.required' => 'El codigo de trabajo es obligatorio.',
            'stars.required' => 'La puntuacion es obligatoria.',
            'stars.integer' => 'La puntuacion debe ser entera.',
            'stars.between' => 'La puntuacion debe estar entre 1 y 5.',
        ];
    }
}

