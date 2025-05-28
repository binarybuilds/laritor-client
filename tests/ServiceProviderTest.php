<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Laritor;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_the_laritor_client_in_the_container()
    {
        $this->assertTrue(
            $this->app->bound(Laritor::class),
            'LaritorClient should be bound in the container'
        );
    }

    /** @test */
    public function it_resolves_the_laritor_client_instance()
    {
        $client = $this->app->make(Laritor::class);
        $this->assertInstanceOf(Laritor::class, $client);
    }
}
