<?php

namespace App\Services;

use App\Models\LegalAcceptance;
use App\Models\User;

class LegalAcceptanceService
{
    /** @param array<int, array{type:string, version:string}> $acceptances */
    public function record(User $user, array $acceptances, ?string $ip, ?string $userAgent): void
    {
        foreach ($acceptances as $acceptance) {
            LegalAcceptance::query()->firstOrCreate([
                'user_id' => (int) $user->id,
                'document_type' => $acceptance['type'],
                'document_version' => $acceptance['version'],
            ], [
                'accepted_at' => now(),
                'ip_address' => $ip,
                'user_agent' => mb_substr((string) $userAgent, 0, 500),
            ]);
        }
    }

    public function currentDocuments(): array
    {
        return collect((array) config('compliance.legal_acceptance.documents', []))
            ->map(fn ($version, $type) => [
                'type' => $type,
                'version' => $version ?: null,
                'url' => $type === 'terms' ? route('legal.terms') : route('legal.privacy'),
            ])->values()->all();
    }
}
