<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Redactor\DataRedactor;
use BinaryBuilds\LaritorClient\Redactor\TestRedactor;

class RedactorTest extends TestCase
{
    public function test_it_registers_the_redactor_in_the_container()
    {
        $this->assertTrue(
            $this->app->bound(DataRedactor::class),
            'DataRedactor should be bound in the container'
        );
    }

    public function test_it_resolves_the_custom_redactor_instance()
    {
        $redactor = $this->app->make(DataRedactor::class);
        $this->assertInstanceOf(TestRedactor::class, $redactor);
    }
}
