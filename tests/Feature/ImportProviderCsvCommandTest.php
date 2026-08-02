<?php

namespace Tests\Feature;

use App\Models\ProviderService;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ProviderCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportProviderCsvCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_persist_imported_providers(): void
    {
        ServiceType::query()->create(['name' => 'Limpieza']);
        ServiceType::query()->create(['name' => 'Reformas integrales']);

        $path = $this->makeCsv([
            'nombre_razon_social,direccion,whatsapp,tipos_servicios,categoria,ciudad,latitude,longitude',
            'LiMPi,"Carrer de Buenaventura Munoz, 12, 08018 Barcelona",+34655427061,"Limpieza semanal del hogar; limpiezas a fondo",Limpieza a domicilio,Barcelona,41.390200,2.190300',
        ]);

        try {
            $this->artisan('providers:import-csv', [
                'file' => $path,
            ])->assertExitCode(0);

            $this->assertSame(0, User::query()->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)->count());
            $this->assertSame(0, UserAddress::query()->count());
            $this->assertSame(0, ProviderService::query()->count());
        } finally {
            @unlink($path);
        }
    }

    public function test_commit_imports_provider_into_canonical_tables_with_null_email_and_landline(): void
    {
        $limpieza = ServiceType::query()->create(['name' => 'Limpieza']);
        $reformas = ServiceType::query()->create(['name' => 'Reformas integrales']);

        $path = $this->makeCsv([
            'nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad,latitude,longitude',
            'LiMPi,"Carrer de Buenaventura Munoz, 12, 08018 Barcelona, España",+34655427061,+34930000001,null,"Limpieza semanal del hogar; limpiezas a fondo",Limpieza a domicilio,Barcelona,41.390200,2.190300',
            'Reformas Siguenza Arias SL,"Carrer de Mallorca, 51-53, Local 3, 08029 Barcelona, España",+34634256204,null,contacto@example.test,"Reformas integrales; cocinas; banos",Reformas integrales,Barcelona,41.384800,2.145100',
        ]);

        try {
            $this->artisan('providers:import-csv', [
                'file' => $path,
                '--commit' => true,
            ])->assertExitCode(0);

            $provider = User::query()
                ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
                ->where('user_name', 'LiMPi')
                ->first();

            $this->assertNotNull($provider);
            $this->assertSame('+34655427061', $provider->phone);
            $this->assertSame('+34930000001', $provider->landline_phone);
            $this->assertNull($provider->email);
            if (Schema::hasColumn('user', 'provider_title')) {
                $this->assertSame('Limpieza a domicilio', $provider->provider_title);
            }

            $address = UserAddress::query()->where('user_id', (int) $provider->id)->first();
            $this->assertNotNull($address);
            $this->assertSame('Barcelona', $address->city);
            $this->assertSame('Barcelona', $address->province);
            $this->assertSame('Barcelona', $address->state);
            $this->assertSame('España', $address->country);
            $this->assertSame('08018', $address->postal_code);

            $this->assertDatabaseHas('provider_services', [
                'provider_id' => (int) $provider->id,
                'service_type_id' => (int) $limpieza->id,
            ]);

            $secondProvider = User::query()
                ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
                ->where('user_name', 'Reformas Siguenza Arias SL')
                ->first();

            $this->assertNotNull($secondProvider);
            $this->assertSame('contacto@example.test', $secondProvider->email);
            $this->assertDatabaseHas('provider_services', [
                'provider_id' => (int) $secondProvider->id,
                'service_type_id' => (int) $reformas->id,
            ]);
        } finally {
            @unlink($path);
        }
    }

    public function test_dry_run_detects_conflict_by_landline_or_name(): void
    {
        ServiceType::query()->create(['name' => 'Pintura']);

        User::query()->create([
            'first_name' => 'MR Reformas',
            'last_name' => null,
            'user_name' => 'MR Reformas',
            'email' => null,
            'phone' => null,
            'landline_phone' => '+34932222228',
            'address' => 'Barcelona',
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'password' => Hash::make('password'),
        ]);

        $path = $this->makeCsv([
            'nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad,latitude,longitude',
            'MR Reformas,Barcelona,null,+34932222228,null,"Pintura interior; pintura exterior",Pintura,Barcelona,41.382580,2.177073',
        ]);

        try {
            $this->artisan('providers:import-csv', [
                'file' => $path,
            ])->assertExitCode(0);

            $this->assertSame(1, User::query()->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)->count());
        } finally {
            @unlink($path);
        }
    }

    public function test_commit_updates_existing_provider_coordinates_without_erasing_existing_contact_data(): void
    {
        $provider = User::query()->create([
            'first_name' => 'RR Multiservicios',
            'last_name' => null,
            'user_name' => 'RR Multiservicios',
            'email' => 'rr@example.test',
            'phone' => '+34688965107',
            'landline_phone' => '+34930001122',
            'address' => 'Direccion anterior',
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'password' => Hash::make('password'),
        ]);

        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Direccion anterior',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'postal_code' => '08001',
            'country' => 'Espana',
            'latitude' => null,
            'longitude' => null,
        ]);

        $path = $this->makeCsv([
            'nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad,latitude,longitude,coordinate_quality',
            'RR Multiservicios,null,+34688965107,null,null,null,null,null,41.400600,2.179500,aproximada_por_direccion',
        ]);

        try {
            $this->artisan('providers:import-csv', [
                'file' => $path,
                '--commit' => true,
                '--update-existing' => true,
            ])->assertExitCode(0);

            $provider->refresh();
            $address = UserAddress::query()->where('user_id', (int) $provider->id)->first();

            $this->assertSame('rr@example.test', $provider->email);
            $this->assertSame('+34930001122', $provider->landline_phone);
            $this->assertNotNull($address);
            $this->assertSame('Direccion anterior', $address->address);
            $this->assertSame('41.400600', $address->latitude);
            $this->assertSame('2.179500', $address->longitude);
        } finally {
            @unlink($path);
        }
    }

    public function test_commit_skips_rows_without_coordinates(): void
    {
        ServiceType::query()->create(['name' => 'Pintura']);

        $path = $this->makeCsv([
            'nombre_razon_social,direccion,whatsapp,tipos_servicios,categoria,ciudad,latitude,longitude',
            'Proveedor Sin Coordenadas,"Carrer Major, 10, 08001 Barcelona",+34611111111,"Pintura interior",Pintura,Barcelona,,',
        ]);

        try {
            $this->artisan('providers:import-csv', [
                'file' => $path,
                '--commit' => true,
            ])->assertExitCode(0);

            $this->assertDatabaseMissing('user', [
                'user_name' => 'Proveedor Sin Coordenadas',
            ]);
        } finally {
            @unlink($path);
        }
    }

    public function test_import_blocks_geolocated_row_when_province_cannot_be_resolved(): void
    {
        ServiceType::query()->create(['name' => 'Pintura']);

        $path = $this->makeCsv([
            'nombre_razon_social,direccion,whatsapp,tipos_servicios,categoria,ciudad,latitude,longitude',
            'Proveedor Ciudad Desconocida,"Calle Mayor, 10, España",+34611111112,"Pintura interior",Pintura,Ciudad Desconocida,40.100000,-3.100000',
        ]);

        try {
            $result = app(ProviderCsvImportService::class)->analyzeFile($path, true);

            $this->assertTrue($result['summary']['blocked']);
            $this->assertSame(1, $result['summary']['missing_province']);
            $this->assertSame(1, $result['summary']['skipped']);
            $this->assertStringContainsString('Provincia no resuelta', $result['report'][0]['observaciones']);
            $this->assertDatabaseMissing('user', [
                'user_name' => 'Proveedor Ciudad Desconocida',
            ]);
        } finally {
            @unlink($path);
        }
    }

    private function makeCsv(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'providers-import-');
        file_put_contents($path, implode(PHP_EOL, $lines));

        return $path;
    }
}
