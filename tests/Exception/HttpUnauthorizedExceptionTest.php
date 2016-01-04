<?php

namespace JeremyKendall\Slim\Auth\Tests\Middleware;

use JeremyKendall\Slim\Auth\Exception\HttpUnauthorizedException;

class HttpUnauthorizedExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetStatusCode()
    {
        $e = new HttpUnauthorizedException();
        $this->assertEquals(401, $e->getStatusCode());
    }

    public function testGetMessage()
    {
        $e = new HttpUnauthorizedException();
        $this->assertEquals('You must authenticate to access this resource.', $e->getMessage());
    }
}
