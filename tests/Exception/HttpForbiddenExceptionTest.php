<?php

namespace JeremyKendall\Slim\Auth\Tests\Middleware;

use JeremyKendall\Slim\Auth\Exception\HttpForbiddenException;

class HttpForbiddenExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetStatusCode()
    {
        $e = new HttpForbiddenException();
        $this->assertEquals(403, $e->getStatusCode());
    }

    public function testGetMessage()
    {
        $e = new HttpForbiddenException();
        $this->assertEquals('You are not authorized to access this resource', $e->getMessage());
    }
}
