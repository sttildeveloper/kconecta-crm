<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\ProfilePhotoService;
use App\Services\ServiceRatingService;
use App\Support\PersonalProfileRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;

class AuthController extends Controller
{
    private const FORGOT_PASSWORD_GENERIC_MESSAGE = 'Si el correo existe, recibiras instrucciones para restablecer tu contrasena.';

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos de autenticacion invalidos.', 422, $validator->errors()->toArray());
        }

        $email = (string) $request->input('email');
        $password = (string) $request->input('password');

        $user = User::where('email', $email)->first();
        if (! $user) {
            return $this->errorResponse('Credenciales incorrectas', 401);
        }

        if (isset($user->is_active) && (int) $user->is_active === 0) {
            return $this->errorResponse('Usuario desactivado', 403);
        }

        $storedPassword = (string) $user->password;
        $hashInfo = Hash::info($storedPassword);
        $validPassword = false;

        if (! empty($hashInfo['algo'])) {
            $validPassword = Hash::check($password, $storedPassword);
        }

        if (! $validPassword) {
            return $this->errorResponse('Credenciales incorrectas', 401);
        }

        Auth::login($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
            'meta' => null,
            'message' => 'Login exitoso',
            'errors' => null,
            // Backward compatibility (legacy mobile/web consumers)
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        return $this->successResponse($this->mePayload($user));
    }

    public function updateMe(Request $request, ProfilePhotoService $profilePhotoService)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        $validator = Validator::make(
            $request->all(),
            PersonalProfileRules::forUpdate($user, true),
            [
                'photo.mimes' => 'La foto debe ser una imagen JPG, JPEG, PNG o WEBP.',
                'photo.max' => 'La foto no puede superar 2MB.',
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse('Datos invalidos.', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $oldPhoto = (string) ($user->photo ?? '');
        $newPhoto = null;

        if ($request->hasFile('photo')) {
            try {
                $newPhoto = $profilePhotoService->store($request->file('photo'), (int) $user->id);
            } catch (\Throwable $exception) {
                report($exception);

                return $this->errorResponse(
                    'No se pudo procesar la foto en este momento.',
                    422,
                    ['photo' => ['No se pudo procesar el archivo enviado.']]
                );
            }
        }

        foreach ([
            'first_name',
            'last_name',
            'email',
            'phone',
            'landline_phone',
            'document_type',
            'document_number',
            'address',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $user->{$field} = $validated[$field];
            }
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make((string) $validated['password']);
        }

        if ($newPhoto !== null) {
            $user->photo = $newPhoto;
        }

        try {
            $user->save();
        } catch (\Throwable $exception) {
            if ($newPhoto !== null) {
                $profilePhotoService->delete($newPhoto);
            }

            throw $exception;
        }

        if ($newPhoto !== null && $oldPhoto !== $newPhoto) {
            $profilePhotoService->delete($oldPhoto);
        }

        return $this->successResponse(
            $this->mePayload($user->fresh()),
            'Perfil actualizado correctamente.'
        );
    }

    private function mePayload(User $user): array
    {
        $providerLogoFile = trim((string) ($user->photo ?? ''));
        $providerLogoFile = $providerLogoFile !== '' ? $providerLogoFile : null;
        $photoPath = $providerLogoFile ? 'img/photo_profile/'.ltrim($providerLogoFile, '/') : null;
        $providerLogoUrl = $photoPath ? asset($photoPath) : null;
        $ratingSummary = $user->isServiceProvider()
            ? app(ServiceRatingService::class)->providerRatingSummary((int) $user->id)
            : null;
        $ratingAvg = $ratingSummary ? (float) ($ratingSummary['average_stars'] ?? 0.0) : null;
        $reviewsCount = $ratingSummary ? (int) ($ratingSummary['ratings_count'] ?? 0) : null;

        return [
            'user' => $user,
            'photo_path' => $photoPath,
            'photo_url' => $providerLogoUrl,
            'email_verified' => $user->email_verified_at !== null,
            'provider_logo_path' => $providerLogoFile,
            'provider_logo_url' => $providerLogoUrl,
            'rating_avg' => $ratingAvg,
            'reviews_count' => $reviewsCount,
        ];
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        $currentToken = $user->currentAccessToken();
        if ($currentToken && ! ($currentToken instanceof TransientToken)) {
            $currentToken->delete();
        } else {
            $rawBearerToken = (string) ($request->bearerToken() ?? '');
            if ($rawBearerToken !== '') {
                $accessToken = PersonalAccessToken::findToken($rawBearerToken);
                if ($accessToken && (int) $accessToken->tokenable_id === (int) $user->id) {
                    $accessToken->delete();
                }
            }
        }

        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->successResponse(null, 'User logged out successfully');
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos invalidos.', 422, $validator->errors()->toArray());
        }

        // Always return a generic response to avoid user enumeration.
        Password::sendResetLink([
            'email' => (string) $request->input('email'),
        ]);

        return $this->successResponse(null, self::FORGOT_PASSWORD_GENERIC_MESSAGE);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos invalidos.', 422, $validator->errors()->toArray());
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make((string) $request->input('password')),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->errorResponse(
                'No se pudo restablecer la contrasena. El token puede ser invalido o haber expirado.',
                400,
                ['token' => [trans($status)]]
            );
        }

        return $this->successResponse(null, 'Contrasena actualizada correctamente.');
    }

    public function deleteAccount(Request $request, AccountDeletionService $deletionService)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos invalidos.', 422, $validator->errors()->toArray());
        }

        if (! Hash::check((string) $request->input('password'), (string) $user->password)) {
            return $this->errorResponse('Credenciales incorrectas.', 401, [
                'password' => ['La contrasena actual no es valida.'],
            ]);
        }

        $deletionService->delete($user, $request);

        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->successResponse(null, 'Cuenta eliminada correctamente.');
    }

    private function successResponse(mixed $data, ?string $message = null, int $status = 200)
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'meta' => null,
            'message' => $message,
            'errors' => null,
            'status' => $status,
        ];

        if (is_array($data) && isset($data['user']) && $data['user'] instanceof User) {
            $payload += [
                // Backward compatibility
                'user' => $data['user'],
                'provider_logo_path' => $data['provider_logo_path'],
                'provider_logo_url' => $data['provider_logo_url'],
                'rating_avg' => $data['rating_avg'],
                'reviews_count' => $data['reviews_count'],
            ];
        }

        return response()->json($payload, $status);
    }

    private function errorResponse(string $message, int $status, ?array $errors = null)
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => null,
            'message' => $message,
            'errors' => $errors,
            'status' => $status,
        ], $status);
    }
}
