<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{

    public function laravel_working()
    {
        $response = $this->get('/');
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 302]),
            "Laravel app isnt responding"
        );
    }
}
