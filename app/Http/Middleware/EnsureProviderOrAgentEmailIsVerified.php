<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderOrAgentEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! env('FORCE_PROVIDER_EMAIL_VERIFICATION', false)) {
            return $next($request);
        }

        $isProviderOrAgent = in_array((int) $user->user_level_id, [
            User::LEVEL_SERVICE_PROVIDER,
            User::LEVEL_AGENT,
        ], true);

        if ($isProviderOrAgent && ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'meta' => null,
                    'message' => 'Tu cuenta requiere verificacion de email.',
                    'errors' => [
                        'code' => 'EMAIL_NOT_VERIFIED',
                    ],
                ], 403);
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
