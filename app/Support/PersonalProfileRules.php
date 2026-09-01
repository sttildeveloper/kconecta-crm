<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rule;

class PersonalProfileRules
{
    public static function forUpdate(User $user, bool $partial = false): array
    {
        $sometimes = $partial ? ['sometimes'] : [];

        return [
            'first_name' => [...$sometimes, 'required', 'string', 'max:255'],
            'last_name' => [...$sometimes, 'nullable', 'string', 'max:255'],
            'email' => [
                ...$sometimes,
                'required',
                'email',
                'max:255',
                Rule::unique('user', 'email')->ignore($user->id),
            ],
            'phone' => [...$sometimes, 'nullable', 'string', 'max:30'],
            'landline_phone' => [...$sometimes, 'nullable', 'string', 'max:30'],
            'document_type' => [...$sometimes, 'nullable', 'string', 'max:25'],
            'document_number' => [...$sometimes, 'nullable', 'string', 'max:50'],
            'address' => [...$sometimes, 'nullable', 'string', 'max:255'],
            'password' => [...$sometimes, 'nullable', 'string', 'min:6'],
            'photo' => [...$sometimes, 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
