<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SanitizeProviderAddressesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_changes_without_persisting_them(): void
    {
        $provider = $this->provider('dry-run@test.dev');
        $address = $this->address($provider, 'Barcelona', 'España', 'Espana', '41.3874', '2.1686');

        $this->artisan('providers:sanitize-addresses')
            ->expectsOutputToContain('Simulación completada')
            ->assertSuccessful();

        $address->refresh();
        $this->assertSame('España', $address->province);
        $this->assertSame('Espana', $address->country);
    }

    public function test_apply_normalizes_geolocated_provider_and_preserves_incomplete_profile(): void
    {
        $provider = $this->provider('apply@test.dev');
        $address = $this->address($provider, 'Barcelona', null, 'Espana', '41.3874', '2.1686');

        $incompleteProvider = $this->provider('incomplete@test.dev');
        $incompleteAddress = $this->address($incompleteProvider, null, null, null, null, null);

        $this->artisan('providers:sanitize-addresses', ['--apply' => true])
            ->expectsOutputToContain('Saneamiento aplicado correctamente: 1 direcciones actualizadas.')
            ->assertSuccessful();

        $address->refresh();
        $this->assertSame('Barcelona', $address->province);
        $this->assertSame('Barcelona', $address->state);
        $this->assertSame('España', $address->country);

        $incompleteAddress->refresh();
        $this->assertNull($incompleteAddress->province);
        $this->assertNull($incompleteAddress->country);
        $this->assertNull($incompleteAddress->latitude);
        $this->assertNull($incompleteAddress->longitude);
    }

    public function test_command_fails_without_writing_when_geolocated_city_is_unknown(): void
    {
        $knownProvider = $this->provider('known@test.dev');
        $knownAddress = $this->address($knownProvider, 'Barcelona', 'España', 'Espana', '41.3874', '2.1686');
        $unknownProvider = $this->provider('unknown@test.dev');
        $this->address($unknownProvider, 'Ciudad no catalogada', null, 'Espana', '40.0', '-3.0');

        $this->artisan('providers:sanitize-addresses', ['--apply' => true])
            ->expectsOutputToContain('Existen ciudades con coordenadas sin provincia resuelta')
            ->assertFailed();

        $knownAddress->refresh();
        $this->assertSame('España', $knownAddress->province);
        $this->assertSame('Espana', $knownAddress->country);
    }

    private function provider(string $email): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Provider',
            'user_name' => 'provider-'.md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);
    }

    private function address(
        User $provider,
        ?string $city,
        ?string $province,
        ?string $country,
        ?string $latitude,
        ?string $longitude
    ): UserAddress {
        return UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'city' => $city,
            'province' => $province,
            'country' => $country,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
