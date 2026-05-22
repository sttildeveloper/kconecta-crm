<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_terms_and_account_deletion_pages_are_public(): void
    {
        $this->get('/legal/privacy')
            ->assertOk()
            ->assertSee('Politica de Privacidad')
            ->assertSee('Responsable del tratamiento')
            ->assertSee('Conservacion de datos')
            ->assertSee('/legal/terms')
            ->assertSee('/legal/account-deletion');

        $this->get('/legal/terms')
            ->assertOk()
            ->assertSee('Terminos y Condiciones')
            ->assertSee('Uso permitido')
            ->assertSee('Jurisdiccion y ley aplicable')
            ->assertSee('/legal/privacy')
            ->assertSee('/legal/account-deletion');

        $this->get('/legal/account-deletion')
            ->assertOk()
            ->assertSee('Eliminacion de Cuenta')
            ->assertSee('Eliminacion desde la app')
            ->assertSee('Datos retenidos y motivo')
            ->assertSee('/legal/privacy')
            ->assertSee('/legal/terms');
    }

    public function test_legacy_privacy_route_redirects_permanently_to_canonical_url(): void
    {
        $this->get('/policy_and_privacy')
            ->assertStatus(301)
            ->assertRedirect('/legal/privacy');
    }
}
