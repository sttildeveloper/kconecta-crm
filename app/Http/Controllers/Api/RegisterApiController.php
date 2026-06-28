<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegisterApiController extends Controller
{
    /**
     * Mobile registration endpoint for service providers.
     */
    public function registerProvider(Request $request, UserRegistrationService $registrationService): JsonResponse
    {
        try {
            $validated = $registrationService->validate($request->all(), User::LEVEL_SERVICE_PROVIDER);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'message' => 'Datos de registro invalidos.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = $registrationService->register($validated);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
            'meta' => null,
            'message' => 'Registro de proveedor exitoso.',
            'errors' => null,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    /**
     * Mobile registration endpoint for final clients.
     */
    public function registerClient(Request $request, UserRegistrationService $registrationService): JsonResponse
    {
        try {
            $validated = $registrationService->validate($request->all(), User::LEVEL_FINAL_CLIENT);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'message' => 'Datos de registro invalidos.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = $registrationService->register($validated);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
            'meta' => null,
            'message' => 'Registro de cliente exitoso.',
            'errors' => null,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }
}
