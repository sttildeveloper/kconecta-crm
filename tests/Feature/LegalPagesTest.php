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
            ->assertSee('/legal/terms')
            ->assertSee('/legal/account-deletion');

        $this->get('/legal/terms')
            ->assertOk()
            ->assertSee('Terminos y Condiciones')
            ->assertSee('/legal/privacy')
            ->assertSee('/legal/account-deletion');

        $this->get('/legal/account-deletion')
            ->assertOk()
            ->assertSee('Eliminacion de Cuenta')
            ->assertSee('/legal/privacy')
            ->assertSee('/legal/terms');
    }
}
