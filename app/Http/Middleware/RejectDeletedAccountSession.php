<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RejectDeletedAccountSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && str_starts_with((string) $user->email, 'deleted+')) {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'data' => null, 'message' => 'Cuenta eliminada.', 'errors' => null], 401);
            }

            return redirect()->route('home');
        }

        return $next($request);
    }
}
