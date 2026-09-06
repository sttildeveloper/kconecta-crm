<?php

namespace Tests\Feature;

use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    public function test_cookie_actions_are_distinct_and_adsense_is_not_loaded_in_the_head(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/home.blade.php'));
        $this->assertStringContainsString('data-cookie-action="accept"', $layout);
        $this->assertStringContainsString('data-cookie-action="deny"', $layout);
        $source = file_get_contents(public_path('js/cookie_config.js'));
        $this->assertStringContainsString('advertising: Boolean', $source);
        $this->assertStringContainsString('denyOptional', $source);
        $details = file_get_contents(resource_path('views/page/details.blade.php'));
        $this->assertStringContainsString("onAllowed('advertising'", $details);
        $this->assertStringNotContainsString('<script async src="https://pagead2.googlesyndication.com', $details);
    }
}
