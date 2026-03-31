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

    public function test_public_landing_page_exposes_open_graph_and_favicon_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('property="og:title" content="Songkran Festival 2026"', false)
            ->assertSee('property="og:description" content="Malaysia&#039;s Premier Songkran Festival. Join us for 11 days of pure celebration!"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('images/Songkran%20logo.png', false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee('rel="shortcut icon"', false);
    }

    public function test_email_verified_page_uses_configured_public_homepage_url(): void
    {
        config()->set('routing.public_url', 'https://songkranfestival.my');

        $response = $this->get('/email/verified');

        $response->assertOk()
            ->assertSee('href="https://songkranfestival.my"', false);
    }

    public function test_email_verified_page_uses_shared_metadata_defaults(): void
    {
        $response = $this->get('/email/verified');

        $response->assertOk()
            ->assertSee('property="og:title" content="Songkran Festival 2026"', false)
            ->assertSee('images/Songkran%20logo.png', false)
            ->assertSee('rel="apple-touch-icon"', false);
    }
}
