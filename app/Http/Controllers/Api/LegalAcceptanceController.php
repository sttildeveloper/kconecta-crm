<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalAcceptance;
use App\Services\LegalAcceptanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalAcceptanceController extends Controller
{
    public function documents(LegalAcceptanceService $service): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'required_on_registration' => (bool) config('compliance.legal_acceptance.required_on_registration', false),
            'documents' => $service->currentDocuments(),
        ]]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => LegalAcceptance::query()
            ->where('user_id', $request->user()->id)->latest('accepted_at')->get()]);
    }

    public function store(Request $request, LegalAcceptanceService $service): JsonResponse
    {
        $types = array_keys((array) config('compliance.legal_acceptance.documents', []));
        $validated = $request->validate([
            'acceptances' => ['required', 'array', 'min:1', 'max:10'],
            'acceptances.*.type' => ['required', Rule::in($types)],
            'acceptances.*.version' => ['required', 'string', 'max:80'],
        ]);
        $service->record($request->user(), $validated['acceptances'], $request->ip(), $request->userAgent());

        return response()->json(['success' => true, 'data' => null, 'message' => 'Aceptación registrada.'], 201);
    }
}
