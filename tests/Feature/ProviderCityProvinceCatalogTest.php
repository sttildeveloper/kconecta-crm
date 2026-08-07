<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProviderCityProvinceCatalogTest extends TestCase
{
    public function test_catalog_contains_every_andalusian_municipality_grouped_by_province(): void
    {
        $catalog = json_decode(
            file_get_contents(database_path('data/provider_city_provinces.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $expectedCounts = [
            'Almería' => 103,
            'Cádiz' => 45,
            'Córdoba' => 77,
            'Granada' => 174,
            'Huelva' => 80,
            'Jaén' => 97,
            'Málaga' => 103,
            'Sevilla' => 106,
        ];

        foreach ($expectedCounts as $province => $expectedCount) {
            $this->assertSame(
                $expectedCount,
                count(array_filter($catalog, fn ($value) => $value === $province)),
                "El total de municipios de {$province} no coincide con el catálogo oficial."
            );
        }

        $this->assertSame(785, array_sum($expectedCounts));
        $this->assertSame('Almería', $catalog['Níjar']);
        $this->assertSame('Cádiz', $catalog['Jerez de la Frontera']);
        $this->assertSame('Córdoba', $catalog['Lucena']);
        $this->assertSame('Granada', $catalog['Órgiva']);
        $this->assertSame('Huelva', $catalog['Lepe']);
        $this->assertSame('Jaén', $catalog['Úbeda']);
        $this->assertSame('Málaga', $catalog['Marbella']);
        $this->assertSame('Sevilla', $catalog['Utrera']);
    }
}
