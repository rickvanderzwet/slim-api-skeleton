<?php

namespace Skeleton\Application\Response;

use PHPUnit\Framework\TestCase;

class NotFoundResponseTest extends TestCase
{
    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }
    public function testShouldBeProblemJson()
    {
        $response = new NotFoundResponse("Yo! MTV Raps");
        $this->assertEquals(
            "application/problem+json",
            $response->getHeader("Content-type")
        );
    }
}
