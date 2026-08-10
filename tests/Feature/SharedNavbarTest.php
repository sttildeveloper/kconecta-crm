<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedNavbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_layouts_include_the_shared_navbar(): void
    {
        $layouts = [
            resource_path('views/layouts/home.blade.php'),
            resource_path('views/layouts/page.blade.php'),
            resource_path('views/legal/layout.blade.php'),
            resource_path('views/placeholder.blade.php'),
        ];

        foreach ($layouts as $layout) {
            $contents = file_get_contents($layout);

            $this->assertIsString($contents);
            $this->assertStringContainsString(
                "@include('layouts.partials.site-navbar')",
                $contents,
                basename($layout).' no incluye la navegación pública compartida.'
            );
        }
    }

    public function test_public_page_families_render_the_home_navigation(): void
    {
        foreach (['/', '/result/services?mode=1', '/legal/privacy'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('class="home-navigation"', false)
                ->assertSee('Servicios')
                ->assertSee('¿Cómo funciona?')
                ->assertSee('Sobre nosotros')
                ->assertSee('Consejos')
                ->assertSee('Novedades')
                ->assertSee('Quiero ser proveedor')
                ->assertSee('Inicia sesión');
        }
    }

    public function test_private_and_auth_layouts_keep_their_own_navigation(): void
    {
        foreach ([
            resource_path('views/layouts/backoffice.blade.php'),
            resource_path('views/auth/auth.blade.php'),
            resource_path('views/auth/verify-email.blade.php'),
            resource_path('views/app.blade.php'),
        ] as $layout) {
            $contents = file_get_contents($layout);

            $this->assertIsString($contents);
            $this->assertStringNotContainsString("@include('layouts.partials.site-navbar')", $contents);
        }
    }

    public function test_mobile_menu_uses_an_accessible_disclosure_control(): void
    {
        $partial = file_get_contents(resource_path('views/layouts/partials/site-navbar.blade.php'));
        $css = file_get_contents(public_path('css/components/site-navbar.css'));

        $this->assertIsString($partial);
        $this->assertIsString($css);
        $this->assertStringContainsString('<div class="home-navigation-disclosure"', $partial);
        $this->assertStringContainsString('aria-expanded="false"', $partial);
        $this->assertStringContainsString(
            '.home-navigation-disclosure.is-open .home-navigation',
            $css
        );
    }
}
