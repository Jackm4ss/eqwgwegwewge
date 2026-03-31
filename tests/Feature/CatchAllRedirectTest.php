<?php

namespace Tests\Feature;

use Tests\TestCase;

class CatchAllRedirectTest extends TestCase
{
    public function test_unknown_web_route_redirects_to_public_landing_page(): void
    {
        config()->set('routing.public_url', 'https://songkranfestival.my');

        $this->get('/halaman/tidak-ada')
            ->assertRedirect('https://songkranfestival.my');
    }

    public function test_unknown_api_route_still_returns_not_found(): void
    {
        $this->getJson('/api/tidak-ada')
            ->assertNotFound();
    }
}
