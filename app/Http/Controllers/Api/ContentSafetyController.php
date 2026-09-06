<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class ContentSafetyController extends Controller
{
    public function report(Request $request): JsonResponse
    {
        $reasons = (array) config('compliance.content_safety.report_reasons', []);
        $validated = $request->validate([
            'reported_user_id' => ['required', 'integer', 'exists:user,id'],
            'content_type' => ['required', Rule::in(['user', 'provider_profile'])],
            'content_id' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', Rule::in($reasons)],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $reporterId = (int) $request->user()->id;
        $reportedId = (int) $validated['reported_user_id'];
        if ($reporterId === $reportedId) {
            return $this->error('No puedes denunciarte a ti mismo.', 422);
        }
        if ($validated['content_type'] === 'provider_profile') {
            $provider = User::query()->whereKey($reportedId)->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)->exists();
            if (! $provider || (isset($validated['content_id']) && (int) $validated['content_id'] !== $reportedId)) {
                return $this->error('El perfil denunciado no corresponde al usuario indicado.', 422);
            }
            $validated['content_id'] = (string) $reportedId;
        }

        $duplicate = ContentReport::query()
            ->where('reporter_user_id', $reporterId)
            ->where('reported_user_id', $reportedId)
            ->where('content_type', $validated['content_type'])
            ->where('content_id', $validated['content_id'] ?? null)
            ->whereIn('status', ['pending', 'reviewing'])
            ->exists();
        if ($duplicate) {
            return $this->error('Ya existe una denuncia activa para este contenido.', 409);
        }

        try {
            $report = ContentReport::query()->create($validated + [
                'reporter_user_id' => $reporterId,
                'status' => 'pending',
                'active_fingerprint' => hash('sha256', implode('|', [$reporterId, $reportedId, $validated['content_type'], $validated['content_id'] ?? ''])),
            ]);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return $this->error('Ya existe una denuncia activa para este contenido.', 409);
            }
            throw $exception;
        }

        return response()->json(['success' => true, 'data' => $report, 'message' => 'Denuncia recibida.'], 201);
    }

    public function blocks(Request $request): JsonResponse
    {
        $blocks = UserBlock::query()->with('blockedUser:id,user_name,first_name,last_name')
            ->where('blocker_user_id', $request->user()->id)->latest()->get();

        return response()->json(['success' => true, 'data' => $blocks]);
    }

    public function block(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return $this->error('No puedes bloquearte a ti mismo.', 422);
        }

        $block = UserBlock::query()->firstOrCreate([
            'blocker_user_id' => (int) $request->user()->id,
            'blocked_user_id' => (int) $user->id,
        ]);

        return response()->json(['success' => true, 'data' => $block], $block->wasRecentlyCreated ? 201 : 200);
    }

    public function unblock(Request $request, User $user): JsonResponse
    {
        UserBlock::query()->where('blocker_user_id', $request->user()->id)
            ->where('blocked_user_id', $user->id)->delete();

        return response()->json(['success' => true, 'data' => null], 200);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'data' => null, 'message' => $message, 'errors' => null], $status);
    }
}
