<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserRegistrationService
{
    /**
     * Validate registration data.
     *
     * @throws ValidationException
     */
    public function validate(array $data, ?int $forcedUserLevelId = null): array
    {
        if ($forcedUserLevelId !== null) {
            $data['user_level_id'] = $forcedUserLevelId;
        }

        $rules = [
            'user_level_id' => 'required|integer|in:' . User::LEVEL_SERVICE_PROVIDER . ',' . User::LEVEL_AGENT . ',' . User::LEVEL_FINAL_CLIENT,
            'document_type' => 'nullable|string|max:25',
            'document_number' => 'nullable|string|max:25',
            'first_name' => 'nullable|required_without:company_name|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'company_name' => 'nullable|required_without:first_name|string|max:100',
            'phone' => 'nullable|string|max:20',
            'landline_phone' => 'nullable|string|max:100',
            'email' => 'required|string|lowercase|email|max:50',
            'password' => ['required', 'confirmed', 'min:6'],
        ];

        $messages = [
            'required' => 'El campo :attribute es obligatorio.',
            'required_without' => 'Debes completar al menos uno de estos campos: :attribute.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'email' => 'El campo :attribute debe ser un correo electronico valido.',
            'confirmed' => 'La confirmacion de :attribute no coincide.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
        ];

        $attributes = [
            'user_level_id' => 'tipo de usuario',
            'document_type' => 'tipo de documento',
            'document_number' => 'numero de documento',
            'first_name' => 'nombre',
            'last_name' => 'apellidos',
            'company_name' => 'razon social',
            'phone' => 'movil (WhatsApp)',
            'landline_phone' => 'telefono fijo',
            'email' => 'e-mail',
            'password' => 'contrasena',
        ];

        $validator = Validator::make($data, $rules, $messages, $attributes);

        $validator->after(function ($validator) use ($data) {
            $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
            $companyName = $this->normalizeCompanyName((string) ($data['company_name'] ?? ''));
            $phone = $this->normalizePhone((string) ($data['phone'] ?? ''));
            $landlinePhone = $this->normalizePhone((string) ($data['landline_phone'] ?? ''));

            $duplicates = [];

            if ($companyName !== '' && User::whereRaw('LOWER(TRIM(user_name)) = ?', [mb_strtolower($companyName)])->exists()) {
                $duplicates['company_name'] = [
                    'label' => 'Razon social',
                    'value' => $companyName,
                ];
                $validator->errors()->add('company_name', 'Ya existe un registro con esta razon social.');
            }

            if ($email !== '' && User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                $duplicates['email'] = [
                    'label' => 'E-mail',
                    'value' => $email,
                ];
                $validator->errors()->add('email', 'Ya existe un registro con este e-mail.');
            }

            if ($phone !== '' && User::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'.',''),'+','') = ?", [$phone])->exists()) {
                $duplicates['phone'] = [
                    'label' => 'WhatsApp',
                    'value' => trim((string) ($data['phone'] ?? '')),
                ];
                $validator->errors()->add('phone', 'Ya existe un registro con este WhatsApp.');
            }

            if ($landlinePhone !== '' && User::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(landline_phone,' ',''),'-',''),'.',''),'+','') = ?", [$landlinePhone])->exists()) {
                $duplicates['landline_phone'] = [
                    'label' => 'Telefono',
                    'value' => trim((string) ($data['landline_phone'] ?? '')),
                ];
                $validator->errors()->add('landline_phone', 'Ya existe un registro con este telefono.');
            }

            if (! empty($duplicates)) {
                $details = collect($duplicates)
                    ->map(fn ($row) => $row['label'] . ': ' . $row['value'])
                    ->implode(' | ');
                $validator->errors()->add(
                    'duplicate_identity',
                    'Ya existe un registro con estos valores: ' . $details
                );
            }

            $firstName = trim((string) ($data['first_name'] ?? ''));
            $companyNameValue = trim((string) ($data['company_name'] ?? ''));
            if ($firstName === '' && $companyNameValue === '') {
                $validator->errors()->add('first_name', 'Completa Nombre o Razon social.');
                $validator->errors()->add('company_name', 'Completa Nombre o Razon social.');
            }
        });

        return $validator->validate();
    }

    /**
     * Register a new user with address and events.
     */
    public function register(array $validatedData): User
    {
        $user = DB::transaction(function () use ($validatedData) {
            $email = (string) $validatedData['email'];
            $userName = $this->normalizeCompanyName((string) ($validatedData['company_name'] ?? ''));
            $documentType = strtoupper(trim((string) ($validatedData['document_type'] ?? '')));
            $documentNumber = $this->normalizeDocumentNumber((string) ($validatedData['document_number'] ?? ''));
            $firstNameInput = trim((string) ($validatedData['first_name'] ?? ''));
            $firstName = $firstNameInput !== '' ? $firstNameInput : $userName;
            $lastName = trim((string) ($validatedData['last_name'] ?? ''));
            $phone = trim((string) ($validatedData['phone'] ?? ''));
            $landlinePhone = trim((string) ($validatedData['landline_phone'] ?? ''));

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName !== '' ? $lastName : null,
                'user_name' => $userName !== '' ? $userName : $firstName,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'landline_phone' => $landlinePhone !== '' ? $landlinePhone : null,
                'document_type' => $documentType !== '' ? $documentType : null,
                'document_number' => $documentNumber !== '' ? $documentNumber : null,
                'address' => null,
                'user_level_id' => (int) $validatedData['user_level_id'],
                'password' => Hash::make($validatedData['password']),
            ]);

            UserAddress::create([
                'user_id' => $user->id,
                'address' => null,
                'street_name' => null,
                'street_number' => null,
                'neighborhood' => null,
                'city' => null,
                'province' => null,
                'postal_code' => null,
                'state' => null,
                'country' => null,
                'latitude' => null,
                'longitude' => null,
                'additional_info' => null,
            ]);

            return $user;
        });

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo de verificacion al registrar usuario en API.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e->getMessage(),
            ]);
        }

        return $user;
    }

    public function normalizeDocumentNumber(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');
    }

    public function normalizeCompanyName(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    public function normalizePhone(string $value): string
    {
        $clean = trim($value);
        return preg_replace('/[\s\-\.\+]/', '', $clean) ?? '';
    }
}
