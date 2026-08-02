<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KconectaBrandAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_brand_assets_and_references_do_not_exist(): void
    {
        $legacyBrand = 'dame'.'lo';
        $legacyLogo = implode('_', [$legacyBrand, $legacyBrand, 'icon']).'.webp';
        $legacyLocationIcon = 'icon'.'-location-main-app.webp';

        $this->assertFileDoesNotExist(public_path('img/'.$legacyLogo));
        $this->assertFileDoesNotExist(public_path('img/'.$legacyLocationIcon));

        $paths = [
            resource_path('views'),
            app_path(),
            public_path('css'),
            public_path('js'),
        ];

        foreach ($paths as $path) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                $this->assertStringNotContainsStringIgnoringCase($legacyBrand, (string) $contents, $file->getPathname());
                $this->assertStringNotContainsString($legacyLocationIcon, (string) $contents, $file->getPathname());
            }
        }
    }

    public function test_kconecta_favicons_and_map_marker_are_available(): void
    {
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('favicon.png'));
        $this->assertFileExists(public_path('favicon.webp'));
        $this->assertFileExists(public_path('img/kconecta-map-marker.png'));

        $this->get('/')
            ->assertOk()
            ->assertSee(asset('favicon.png'), false)
            ->assertDontSee('img/ico.png', false);
    }
}
