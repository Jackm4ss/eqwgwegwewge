<?php

namespace Tests\Unit;

use App\Support\AppRouting;
use Tests\TestCase;

class AppRoutingTest extends TestCase
{
    public function test_subdomain_routing_uses_separate_public_register_admin_and_staff_hosts(): void
    {
        config()->set('routing.mode', 'subdomain');
        config()->set('routing.public_url', 'https://songkranfestival.my');
        config()->set('routing.register_url', 'https://register.songkranfestival.my');
        config()->set('routing.admin_url', 'https://portal.songkranfestival.my');
        config()->set('routing.staff_url', 'https://app.songkranfestival.my');

        $this->assertTrue(AppRouting::isSubdomainMode());
        $this->assertSame('songkranfestival.my', AppRouting::hostFor('public'));
        $this->assertSame('register.songkranfestival.my', AppRouting::hostFor('register'));
        $this->assertSame('portal.songkranfestival.my', AppRouting::hostFor('admin'));
        $this->assertSame('app.songkranfestival.my', AppRouting::hostFor('staff'));
    }
}
