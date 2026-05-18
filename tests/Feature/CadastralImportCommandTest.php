<?php

namespace Tests\Feature;

use App\Models\CadastralPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CadastralImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastral_import_creates_rows_from_csv(): void
    {
        $file = $this->makeCsv([
            ['province', 'municipality', 'neighborhood', 'postal_code', 'price_m2_eur'],
            ['Barcelona', 'Barcelona', 'Gracia', '08012', '3200.50'],
            ['Barcelona', 'Barcelona', 'Sants', '08014', '2800.00'],
        ]);

        $exit = Artisan::call('cadastral:import', ['file' => $file]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseHas('cadastral_prices', [
            'postal_code' => '08012',
            'municipality' => 'Barcelona',
            'neighborhood' => 'Gracia',
            'price_m2_eur' => 3200.5,
        ]);
        $this->assertDatabaseHas('cadastral_prices', [
            'postal_code' => '08014',
            'municipality' => 'Barcelona',
            'neighborhood' => 'Sants',
            'price_m2_eur' => 2800.0,
        ]);
    }

    public function test_cadastral_import_upsert_updates_existing_row(): void
    {
        CadastralPrice::query()->create([
            'province' => 'Barcelona',
            'municipality' => 'Barcelona',
            'neighborhood' => 'Gracia',
            'postal_code' => '08012',
            'price_m2_eur' => 2000,
            'import_batch_id' => 'old-batch',
        ]);

        $file = $this->makeCsv([
            ['province', 'municipality', 'neighborhood', 'postal_code', 'price_m2_eur'],
            ['Barcelona', 'Barcelona', 'Gracia', '08012', '3500.00'],
        ]);

        $exit = Artisan::call('cadastral:import', ['file' => $file]);

        $this->assertSame(0, $exit);
        $this->assertSame(1, CadastralPrice::query()->count());
        $this->assertDatabaseHas('cadastral_prices', [
            'postal_code' => '08012',
            'municipality' => 'Barcelona',
            'neighborhood' => 'Gracia',
            'price_m2_eur' => 3500.0,
        ]);
    }

    private function makeCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cadastral_');
        $handle = fopen($path, 'wb');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }
}

