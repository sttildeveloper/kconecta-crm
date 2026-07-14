<?php

namespace Tests\Feature;

use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminProviderCsvImportPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_csv_import_and_see_conflicts(): void
    {
        Storage::fake('local');

        ServiceType::query()->create(['name' => 'Pintura']);
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-import-preview@test.dev');

        User::query()->create([
            'first_name' => 'Pintores Barcelona',
            'last_name' => null,
            'user_name' => 'Pintores Barcelona',
            'email' => null,
            'phone' => '+34611111111',
            'landline_phone' => null,
            'address' => 'Barcelona',
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'password' => Hash::make('password'),
        ]);

        $csv = UploadedFile::fake()->createWithContent('providers.csv', implode(PHP_EOL, [
            'nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad,latitude,longitude',
            'Pintores Barcelona,"Carrer Major, 10, 08001 Barcelona",+34611111111,null,null,"Pintura interior",Pintura,Barcelona,41.385100,2.173400',
            'Nuevo Pintor,"Carrer Nou, 20, 08002 Barcelona",+34622222222,null,null,"Pintura exterior",Pintura,Barcelona,41.381200,2.176500',
        ]));

        $this->actingAs($admin)
            ->withCsrfToken()
            ->post('/users/providers/import/preview', [
                'providers_csv' => $csv,
                'level' => User::LEVEL_SERVICE_PROVIDER,
            ])
            ->assertRedirect('/users?level=4')
            ->assertSessionHas('provider_import_preview')
            ->assertSessionHas('status');

        $this->actingAs($admin)
            ->get('/users?level=4')
            ->assertOk()
            ->assertSee('Resumen previo')
            ->assertSee('Pintores Barcelona')
            ->assertSee('Nuevo Pintor')
            ->assertSee('duplicados/conflictos')
            ->assertSee('Cancelar')
            ->assertSee('Proceder');
    }

    public function test_admin_can_commit_previewed_csv_import(): void
    {
        Storage::fake('local');

        ServiceType::query()->create(['name' => 'Reformas integrales']);
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-import-commit@test.dev');

        $csvContents = implode(PHP_EOL, [
            'nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad,latitude,longitude',
            'Reformas Kconecta,"Carrer Industria, 15, 08037 Barcelona",+34633333333,null,null,"Reformas integrales",Reformas integrales,Barcelona,41.403500,2.181200',
        ]);

        Storage::disk('local')->put('provider-imports/test-preview.csv', $csvContents);

        $this->actingAs($admin)
            ->withSession([
                'provider_import_preview' => [
                    'storage_path' => 'provider-imports/test-preview.csv',
                    'original_name' => 'test-preview.csv',
                    'uploaded_at' => now()->format('d/m/Y H:i'),
                    'summary' => [
                        'rows' => 1,
                        'created' => 1,
                        'updated' => 0,
                        'skipped' => 0,
                        'conflicts' => 0,
                        'unmapped' => 0,
                        'errors' => 0,
                    ],
                    'report' => [],
                ],
                '_token' => 'test-csrf-token',
            ])
            ->post('/users/providers/import/commit')
            ->assertRedirect('/users?level=4')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('user', [
            'user_name' => 'Reformas Kconecta',
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'phone' => '+34633333333',
            'email' => null,
        ]);

        $this->assertDatabaseMissing('user', [
            'user_name' => 'Reformas Kconecta',
            'email' => '',
        ]);

        Storage::disk('local')->assertMissing('provider-imports/test-preview.csv');
    }

    public function test_admin_preview_shows_coordinate_updates_for_existing_provider(): void
    {
        Storage::fake('local');

        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-import-coords@test.dev');
        $provider = User::query()->create([
            'first_name' => 'RR Multiservicios',
            'last_name' => null,
            'user_name' => 'RR Multiservicios',
            'email' => 'rr@example.test',
            'phone' => '+34688965107',
            'password' => Hash::make('password'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
        ]);

        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Carrer de Lepant, 247',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'country' => 'Espana',
        ]);

        $csv = UploadedFile::fake()->createWithContent('providers-with-coords.csv', implode(PHP_EOL, [
            'nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad,latitude,longitude,coordinate_quality',
            'RR Multiservicios,"Carrer de Lepant, 247, 08013 Barcelona",+34688965107,null,null,null,null,Barcelona,41.400600,2.179500,aproximada_por_direccion',
        ]));

        $this->actingAs($admin)
            ->withCsrfToken()
            ->post('/users/providers/import/preview', [
                'providers_csv' => $csv,
                'level' => User::LEVEL_SERVICE_PROVIDER,
            ])
            ->assertRedirect('/users?level=4')
            ->assertSessionHas('provider_import_preview');

        $this->actingAs($admin)
            ->get('/users?level=4')
            ->assertOk()
            ->assertSee('RR Multiservicios')
            ->assertSee('Null')
            ->assertDontSee('Sin e-mail')
            ->assertDontSee('Sin Telefono fijo')
            ->assertDontSee('Sin coordenadas');
    }

    public function test_admin_preview_returns_error_message_when_analysis_fails(): void
    {
        Storage::fake('local');

        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-import-error@test.dev');
        $csv = UploadedFile::fake()->createWithContent('broken.csv', "nombre_razon_social\nProveedor");

        $this->mock(\App\Services\ProviderCsvImportService::class, function (MockInterface $mock) {
            $mock->shouldReceive('analyzeFile')
                ->once()
                ->andThrow(new \RuntimeException('CSV invalido para importacion'));
        });

        $this->actingAs($admin)
            ->withCsrfToken()
            ->post('/users/providers/import/preview', [
                'providers_csv' => $csv,
                'level' => User::LEVEL_SERVICE_PROVIDER,
            ])
            ->assertRedirect('/users?level=4')
            ->assertSessionHas('error');
    }

    public function test_admin_preview_blocks_import_when_csv_has_missing_coordinates(): void
    {
        Storage::fake('local');

        ServiceType::query()->create(['name' => 'Pintura']);
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-import-missing-coords@test.dev');

        $csv = UploadedFile::fake()->createWithContent('providers-missing-coords.csv', implode(PHP_EOL, [
            'nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad,latitude,longitude',
            'Pintores Sin Mapa,"Carrer Major, 10, 08001 Barcelona",+34611111111,null,null,"Pintura interior",Pintura,Barcelona,,',
        ]));

        $this->actingAs($admin)
            ->withCsrfToken()
            ->post('/users/providers/import/preview', [
                'providers_csv' => $csv,
                'level' => User::LEVEL_SERVICE_PROVIDER,
            ])
            ->assertRedirect('/users?level=4')
            ->assertSessionHas('provider_import_preview');

        $this->actingAs($admin)
            ->get('/users?level=4')
            ->assertOk()
            ->assertSee('sin coordenadas')
            ->assertSee('Warning: el CSV contiene proveedores sin coordenadas')
            ->assertSee('Faltan coordenadas')
            ->assertSee('disabled', false);
    }

    public function test_admin_cannot_commit_preview_when_missing_coordinates_are_detected(): void
    {
        Storage::fake('local');

        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-import-blocked-commit@test.dev');

        Storage::disk('local')->put('provider-imports/test-preview-blocked.csv', implode(PHP_EOL, [
            'nombre_razon_social,direccion,whatsapp,landing_phone,email,tipos_servicios,categoria,ciudad,latitude,longitude',
            'Proveedor Sin Coordenadas,"Carrer Major, 10, 08001 Barcelona",+34611111111,null,null,null,null,Barcelona,,',
        ]));

        $this->actingAs($admin)
            ->withSession([
                'provider_import_preview' => [
                    'storage_path' => 'provider-imports/test-preview-blocked.csv',
                    'original_name' => 'test-preview-blocked.csv',
                    'uploaded_at' => now()->format('d/m/Y H:i'),
                    'summary' => [
                        'rows' => 1,
                        'created' => 0,
                        'updated' => 0,
                        'skipped' => 1,
                        'conflicts' => 0,
                        'unmapped' => 0,
                        'missing_coordinates' => 1,
                        'errors' => 0,
                        'blocked' => true,
                    ],
                    'report' => [],
                ],
                '_token' => 'test-csrf-token',
            ])
            ->post('/users/providers/import/commit')
            ->assertRedirect('/users?level=4')
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('user', [
            'user_name' => 'Proveedor Sin Coordenadas',
        ]);
    }

    private function makeUser(int $levelId, string $email): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'user_name' => 'user-' . md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password'),
            'user_level_id' => $levelId,
            'email_verified_at' => now(),
        ]);
    }
}
