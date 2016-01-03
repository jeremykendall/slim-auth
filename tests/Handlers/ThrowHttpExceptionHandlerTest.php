<?php

namespace JeremyKendall\Slim\Auth\Tests\Middleware;

use JeremyKendall\Slim\Auth\Handlers\ThrowHttpExceptionHandler;
use Psr\Http\Message\ResponseInterface;

class ThrowHttpExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $handler;
    private $response;

    protected function setUp()
    {
        parent::setUp();
        $this->handler = new ThrowHttpExceptionHandler();
        $this->response = $this->getMock('Psr\Http\Message\ResponseInterface');
    }

    protected function tearDown()
    {
        $this->handler = null;
        parent::tearDown();
    }

    public function testNotAuthenticatedThrowsForbidden()
    {
        $this->setExpectedException('JeremyKendall\Slim\Auth\Exception\HttpForbiddenException');
        $this->handler->notAuthenticated($this->response);
    }

    public function testNotAuthenticatedThrowsUnauthorized()
    {
        $this->setExpectedException('JeremyKendall\Slim\Auth\Exception\HttpUnauthorizedException');
        $this->handler->notAuthorized($this->response);
    }
}
