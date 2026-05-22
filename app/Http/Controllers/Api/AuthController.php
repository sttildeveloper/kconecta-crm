<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ServiceRatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;
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

        $providerLogoPath = trim((string) ($user->photo ?? ''));
        $providerLogoPath = $providerLogoPath !== '' ? $providerLogoPath : null;
        $providerLogoUrl = $providerLogoPath ? asset('img/photo_profile/' . ltrim($providerLogoPath, '/')) : null;
        $ratingSummary = $user->isServiceProvider()
            ? app(ServiceRatingService::class)->providerRatingSummary((int) $user->id)
            : null;
        $ratingAvg = $ratingSummary ? (float) ($ratingSummary['average_stars'] ?? 0.0) : null;
        $reviewsCount = $ratingSummary ? (int) ($ratingSummary['ratings_count'] ?? 0) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'provider_logo_path' => $providerLogoPath,
                'provider_logo_url' => $providerLogoUrl,
                'rating_avg' => $ratingAvg,
                'reviews_count' => $reviewsCount,
            ],
            'meta' => null,
            'message' => null,
            'errors' => null,
            // Backward compatibility
            'user' => $user,
            'provider_logo_path' => $providerLogoPath,
            'provider_logo_url' => $providerLogoUrl,
            'rating_avg' => $ratingAvg,
            'reviews_count' => $reviewsCount,
        ]);
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

    public function deleteAccount(Request $request)
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

        $userId = (int) $user->id;
        $deletedAt = now();
        $deletedSuffix = $userId . '_' . $deletedAt->timestamp;
        $reason = trim((string) $request->input('reason', ''));
        $actorIp = (string) $request->ip();
        $actorUserAgent = substr((string) $request->userAgent(), 0, 255);
        $deletedEmailDomain = (string) config('legal.deleted_user_email_domain', 'kconecta.local');

        DB::transaction(function () use ($user, $userId, $deletedSuffix, $reason, $actorIp, $actorUserAgent, $deletedAt, $deletedEmailDomain) {
            $payload = [
                'first_name' => 'Cuenta eliminada',
                'last_name' => null,
                'user_name' => 'deleted-user-' . $userId,
                'email' => 'deleted+' . $deletedSuffix . '@' . ltrim($deletedEmailDomain, '@'),
                'phone' => null,
                'landline_phone' => null,
                'document_type' => null,
                'document_number' => null,
                'address' => null,
                'photo' => null,
                'email_verified_at' => null,
                'remember_token' => Str::random(60),
                'password' => Hash::make(Str::random(64)),
            ];

            if (Schema::hasColumn('user', 'is_active')) {
                $payload['is_active'] = 0;
            }

            $user->forceFill($payload)->save();

            if (Schema::hasTable('user_address')) {
                DB::table('user_address')->where('user_id', $userId)->delete();
            }

            if (Schema::hasTable('personal_access_tokens')) {
                $user->tokens()->delete();
            }

            if (Schema::hasTable('account_deletion_audits')) {
                DB::table('account_deletion_audits')->insert([
                    'user_id' => $userId,
                    'requested_reason' => $reason !== '' ? $reason : null,
                    'requested_ip' => $actorIp !== '' ? $actorIp : null,
                    'requested_user_agent' => $actorUserAgent !== '' ? $actorUserAgent : null,
                    'created_at' => $deletedAt,
                    'updated_at' => $deletedAt,
                ]);
            }
        });

        Log::info('account_deleted', [
            'user_id' => $userId,
            'ip' => $actorIp,
            'reason_present' => $reason !== '',
        ]);

        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->successResponse(null, 'Cuenta eliminada correctamente.');
    }

    private function successResponse(mixed $data, ?string $message = null, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => null,
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    private function errorResponse(string $message, int $status, ?array $errors = null)
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
