<?php

namespace Tests\Feature;

use App\Models\ProviderService;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExportProviderCsvCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_service_providers_in_importable_csv_format(): void
    {
        $limpieza = ServiceType::query()->create(['name' => 'Limpieza']);
        $pintura = ServiceType::query()->create(['name' => 'Pintura']);

        $provider = User::query()->create([
            'first_name' => 'Limpiezas',
            'last_name' => 'BCN',
            'user_name' => 'Limpiezas BCN',
            'email' => null,
            'phone' => '+34600111222',
            'landline_phone' => null,
            'address' => 'Carrer de Sants, 10, 08014 Barcelona',
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'password' => Hash::make('password'),
        ]);

        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Carrer de Sants, 10, 08014 Barcelona',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'postal_code' => '08014',
            'country' => 'Espana',
        ]);

        ProviderService::query()->insert([
            [
                'provider_id' => (int) $provider->id,
                'service_type_id' => (int) $limpieza->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_id' => (int) $provider->id,
                'service_type_id' => (int) $pintura->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $path = storage_path('app/testing/providers-export-test.csv');
        @unlink($path);

        try {
            $this->artisan('providers:export-csv', [
                '--path' => $path,
            ])->assertExitCode(0);

            $this->assertFileExists($path);
            $contents = file_get_contents($path);

            $this->assertIsString($contents);
            $this->assertStringContainsString('nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad', $contents);
            $this->assertStringContainsString('Limpiezas BCN', $contents);
            $this->assertStringContainsString('+34600111222', $contents);
            $this->assertStringContainsString('Limpieza; Pintura', $contents);
            $this->assertStringContainsString(',null,', $contents);
        } finally {
            @unlink($path);
        }
    }
}
