<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_document_layout_includes_the_shared_footer(): void
    {
        $layouts = [
            resource_path('views/layouts/home.blade.php'),
            resource_path('views/layouts/page.blade.php'),
            resource_path('views/layouts/backoffice.blade.php'),
            resource_path('views/legal/layout.blade.php'),
            resource_path('views/auth/auth.blade.php'),
            resource_path('views/auth/verify-email.blade.php'),
            resource_path('views/app.blade.php'),
            resource_path('views/placeholder.blade.php'),
        ];

        foreach ($layouts as $layout) {
            $contents = file_get_contents($layout);

            $this->assertIsString($contents);
            $this->assertStringContainsString(
                "@include('layouts.partials.site-footer')",
                $contents,
                basename($layout).' no incluye el footer compartido.'
            );
        }
    }

    public function test_public_layout_families_render_the_same_footer(): void
    {
        foreach (['/', '/result/services?mode=1', '/legal/privacy', '/login'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('class="site-footer"', false)
                ->assertSee('La forma más sencilla de encontrar profesionales de confianza para tu hogar.')
                ->assertSee('Eliminación de cuenta');
        }
    }
}
