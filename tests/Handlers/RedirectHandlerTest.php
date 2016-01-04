<?php

namespace JeremyKendall\Slim\Auth\Tests\Middleware;

use JeremyKendall\Slim\Auth\Handlers\RedirectHandler;
use Slim\Http\Response;

class RedirectHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $handler;
    private $notAuthenticatedRedirect;
    private $notAuthorizedRedirect;
    private $response;

    protected function setUp()
    {
        parent::setUp();
        $this->notAuthenticatedRedirect = '/not-authenticted-redirect';
        $this->notAuthorizedRedirect = '/403';
        $this->handler = new RedirectHandler(
            $this->notAuthenticatedRedirect,
            $this->notAuthorizedRedirect
        );
        $this->response = new Response();
    }

    protected function tearDown()
    {
        $this->handler = null;
        parent::tearDown();
    }

    public function testNotAuthenticatedRedirectsToNotAuthenticatedRedirect()
    {
        $response = $this->handler->notAuthenticated($this->response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            $this->notAuthenticatedRedirect,
            $response->getHeaderLine('Location')
        );
    }

    public function testNotAuthorizedRedirectsToNotAuthorizedRedirect()
    {
        $response = $this->handler->notAuthorized($this->response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            $this->notAuthorizedRedirect,
            $response->getHeaderLine('Location')
        );
    }
}
