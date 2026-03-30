<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_public_landing_page_exposes_configured_register_url_meta(): void
    {
        config()->set('admin.future_urls.register', 'https://register.songkran.my');

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('name="register-url" content="https://register.songkran.my"', false);
    }
}
