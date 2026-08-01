<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_the_approved_public_services_experience(): void
    {
        $now = now();

        DB::table('service_type')->insert([
            ['id' => 1, 'name' => 'Fontanería', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Electricidad', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Carpintería', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Enfermería', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Encuentra profesionales de confianza para tu hogar')
            ->assertSee('Servicios más buscados')
            ->assertSee('Así funciona Kconecta')
            ->assertSee('Lo que dicen nuestros usuarios')
            ->assertSee('Consejos para tu hogar')
            ->assertSee('Haz crecer tu negocio con Kconecta')
            ->assertSee('hero-bg.webp')
            ->assertSee('img-review-1.webp')
            ->assertSee('img-review-2.webp')
            ->assertSee('img-review-3.webp')
            ->assertSee('/result/services', false)
            ->assertSee('data-home-city', false)
            ->assertSee('data-home-province', false)
            ->assertSee('Código postal o dirección')
            ->assertSee('Busca y contacta sin registrarte.')
            ->assertSee('Regístrate como proveedor')
            ->assertSee('Fontanería')
            ->assertSee('Electricidad')
            ->assertSee('Carpintería')
            ->assertDontSee('Enfermería');
    }

    public function test_home_shows_only_the_three_most_recent_published_articles(): void
    {
        DB::table('blog_posts')->insert([
            $this->article(1, 'Consejo antiguo', 'consejo-antiguo', '2026-01-01 10:00:00', 1),
            $this->article(2, 'Consejo reciente dos', 'consejo-reciente-dos', '2026-01-02 10:00:00', 1),
            $this->article(3, 'Consejo reciente tres', 'consejo-reciente-tres', '2026-01-03 10:00:00', 1),
            $this->article(4, 'Consejo más reciente', 'consejo-mas-reciente', '2026-01-04 10:00:00', 1),
            $this->article(5, 'Borrador invisible', 'borrador-invisible', '2026-01-05 10:00:00', 0),
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Consejo más reciente',
                'Consejo reciente tres',
                'Consejo reciente dos',
            ])
            ->assertDontSee('Consejo antiguo')
            ->assertDontSee('Borrador invisible')
            ->assertSee('/blogs/consejo-mas-reciente', false)
            ->assertSee('Ver todos los consejos');

        $this->assertSame(3, substr_count($response->getContent(), 'class="home-article-card"'));
    }

    public function test_public_blog_uses_the_same_publication_order_and_hides_drafts(): void
    {
        DB::table('blog_posts')->insert([
            $this->article(1, 'Primero publicado', 'primero-publicado', '2026-01-01 10:00:00', 1),
            $this->article(2, 'Último publicado', 'ultimo-publicado', '2026-01-03 10:00:00', 1),
            $this->article(3, 'Segundo publicado', 'segundo-publicado', '2026-01-02 10:00:00', 1),
            $this->article(4, 'Borrador reciente', 'borrador-reciente', '2026-01-04 10:00:00', 0),
        ]);

        $this->get('/blogs')
            ->assertOk()
            ->assertSeeInOrder(['Último publicado', 'Segundo publicado', 'Primero publicado'])
            ->assertSee('Servicios')
            ->assertSee('Novedades')
            ->assertSee('Quiero ser proveedor')
            ->assertDontSee('Publica tus propiedades')
            ->assertDontSee('Proveedor de servicios')
            ->assertDontSee('Borrador reciente');
    }

    public function test_geolocation_is_only_requested_from_the_explicit_location_button_handler(): void
    {
        $javascript = file_get_contents(public_path('js/home.js'));

        $this->assertIsString($javascript);
        $this->assertStringContainsString('useLocationButton?.addEventListener("click"', $javascript);
        $this->assertStringContainsString('navigator.geolocation.getCurrentPosition(', $javascript);
        $this->assertStringContainsString('google.maps.places.Autocomplete', $javascript);
        $this->assertStringContainsString('AutocompleteSuggestion.fetchAutocompleteSuggestions', $javascript);
        $this->assertStringContainsString('componentRestrictions: { country: "es" }', $javascript);
        $this->assertStringContainsString('reverseGeocodeCoordinates', $javascript);
        $this->assertSame(1, substr_count($javascript, 'navigator.geolocation.getCurrentPosition('));
    }

    private function article(int $id, string $title, string $slug, string $createdAt, int $status): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'summary' => 'Resumen de '.$title,
            'featured_image' => 'img/article/'.$slug.'.webp',
            'content' => '<p>Contenido</p>',
            'status' => $status,
            'blog_post_category_id' => 7,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
