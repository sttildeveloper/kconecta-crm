<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Services\UserRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.auth', [
            'mode' => 'register',
            'userLevels' => UserLevel::query()
                ->whereIn('id', [User::LEVEL_SERVICE_PROVIDER, User::LEVEL_AGENT, User::LEVEL_FINAL_CLIENT])
                ->orderBy('id')
                ->get(),
            'documentTypes' => $this->documentTypes(),
            'mapsKey' => (string) config('services.google.maps_key'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, UserRegistrationService $registrationService): RedirectResponse
    {
        $validated = $registrationService->validate($request->all());
        $user = $registrationService->register($validated);

        Auth::login($user);

        return redirect($this->redirectPathForUser($user));
    }

    private function documentTypes(): array
    {
        return [
            'DNI',
            'NIE',
            'CIF',
            'Pasaporte',
            'Otro',
        ];
    }

    private function normalizeDocumentNumber(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');
    }

    private function normalizeCompanyName(string $value): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        return $clean;
    }

    private function normalizePhone(string $value): string
    {
        $clean = trim($value);
        return preg_replace('/[\s\-\.\+]/', '', $clean) ?? '';
    }

    private function composeRegistrationAddress(string $address, string $floor, string $door): string
    {
        $baseAddress = trim($address);
        $parts = [];
        $floorValue = trim($floor);
        $doorValue = trim($door);

        if ($floorValue !== '') {
            $parts[] = 'Piso: ' . $floorValue;
        }
        if ($doorValue !== '') {
            $parts[] = 'Puerta: ' . $doorValue;
        }

        if (empty($parts)) {
            return $baseAddress;
        }

        return $baseAddress . ' (' . implode(', ', $parts) . ')';
    }

    private function nullableTrim(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function isValidSpanishDocument(string $documentType, string $documentNumber): bool
    {
        if ($documentNumber === '') {
            return false;
        }

        if ($documentType === 'DNI') {
            return $this->isValidSpanishDni($documentNumber);
        }

        if ($documentType === 'NIE') {
            return $this->isValidSpanishNie($documentNumber);
        }

        if ($documentType === 'CIF') {
            return $this->isValidSpanishCif($documentNumber);
        }

        return true;
    }

    private function isValidSpanishDni(string $value): bool
    {
        if (! preg_match('/^\d{8}[A-Z]$/', $value)) {
            return false;
        }

        $number = (int) substr($value, 0, 8);
        $letter = substr($value, -1);
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';

        return $letter === $letters[$number % 23];
    }

    private function isValidSpanishNie(string $value): bool
    {
        if (! preg_match('/^[XYZ]\d{7}[A-Z]$/', $value)) {
            return false;
        }

        $prefixMap = ['X' => '0', 'Y' => '1', 'Z' => '2'];
        $prefix = substr($value, 0, 1);
        $converted = $prefixMap[$prefix] . substr($value, 1);

        return $this->isValidSpanishDni($converted);
    }

    private function isValidSpanishCif(string $value): bool
    {
        if (! preg_match('/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/', $value)) {
            return false;
        }

        $letter = substr($value, 0, 1);
        $digits = substr($value, 1, 7);
        $control = substr($value, -1);

        $sumEven = 0;
        $sumOdd = 0;
        for ($index = 0; $index < 7; $index++) {
            $digit = (int) $digits[$index];
            if (($index + 1) % 2 === 0) {
                $sumEven += $digit;
                continue;
            }

            $doubled = $digit * 2;
            $sumOdd += intdiv($doubled, 10) + ($doubled % 10);
        }

        $sum = $sumEven + $sumOdd;
        $controlDigit = (10 - ($sum % 10)) % 10;
        $controlLetter = 'JABCDEFGHI'[$controlDigit];

        if (in_array($letter, ['A', 'B', 'E', 'H'], true)) {
            return $control === (string) $controlDigit;
        }

        if (in_array($letter, ['K', 'P', 'Q', 'S', 'W'], true)) {
            return $control === $controlLetter;
        }

        return $control === (string) $controlDigit || $control === $controlLetter;
    }

    private function redirectPathForUser(User $user): string
    {
        if ($this->requiresEmailVerification($user) && ! $user->hasVerifiedEmail()) {
            return route('verification.notice', absolute: false);
        }

        if ($user->isAdmin()) {
            return route('dashboard', absolute: false);
        }

        if ($user->canManageServices() && ! $user->canManageProperties()) {
            return url('/post/services');
        }

        if ($user->canManageProperties()) {
            return url('/post/my_posts');
        }

        return route('dashboard', absolute: false);
    }

    private function requiresEmailVerification(User $user): bool
    {
        return in_array((int) $user->user_level_id, [
            User::LEVEL_SERVICE_PROVIDER,
            User::LEVEL_AGENT,
            User::LEVEL_FINAL_CLIENT,
        ], true);
    }
}
