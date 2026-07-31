<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProviderLandingHeroTest extends TestCase
{
    public function test_provider_landing_uses_the_simplified_hero_without_overlays(): void
    {
        $response = $this->get('/quiero-ser-proveedor');

        $response
            ->assertOk()
            ->assertSee('Tú tienes el')
            ->assertSee('Nosotros te')
            ->assertSee('llevamos clientes.')
            ->assertSee('img-hero-landing-quiero.webp')
            ->assertSee('Desde que estoy en Kconecta, tengo más trabajo y mejores clientes.')
            ->assertSee('Empieza hoy, sin complicaciones.')
            ->assertSee('REGÍSTRATE COMO PROVEEDOR')
            ->assertSee('Servicios')
            ->assertSee('¿Cómo funciona?')
            ->assertSee('Sobre nosotros')
            ->assertSee('Consejos')
            ->assertSee('Novedades')
            ->assertSee('Quiero ser proveedor')
            ->assertSee('name="user_level_id"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('Publica tus propiedades')
            ->assertDontSee('Proveedor de servicios')
            ->assertDontSee('provider-hero-skyline.png')
            ->assertDontSee('banner-hero-landing-quiero.webp');

        $this->assertMatchesRegularExpression(
            '/provider_landing\.css\?v=\d+/',
            $response->getContent()
        );
    }
}
