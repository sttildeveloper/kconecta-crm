<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
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

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
            ],
            'meta' => null,
            'message' => null,
            'errors' => null,
            // Backward compatibility
            'user' => $user,
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

        return response()->json([
            'success' => true,
            'data' => null,
            'meta' => null,
            'message' => 'User logged out successfully',
            'errors' => null,
        ]);
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
