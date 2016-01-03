<?php

namespace JeremyKendall\Slim\Auth\Tests\Middleware;

use JeremyKendall\Slim\Auth\Middleware\Authorization;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;

class AuthorizationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Zend\Authentication\AuthenticationServiceInterface
     */
    private $auth;

    /**
     * @var Zend\Permissions\Acl\AclInterface
     */
    private $acl;

    /**
     * @var Authorization
     */
    private $middleware;

    protected function setUp()
    {
        parent::setUp();

        $this->auth = $this->getMock('Zend\Authentication\AuthenticationServiceInterface');
        $this->acl = $this->getConfiguredAcl();
    }

    protected function tearDown()
    {
        $this->middleware = null;
        $this->acl = null;
        parent::tearDown();
    }

    /**
     * @dataProvider authenticationDataProvider
     */
    public function testRouteAuthentication(
        $requestMethod,
        $path,
        $location,
        $hasIdentity,
        $identity,
        $httpStatus,
        $pattern
    ) {
        $env = Environment::mock([
            'REQUEST_METHOD' => $requestMethod,
            'REQUEST_URI' => $path,
        ]);

        $request = Request::createFromEnvironment($env);
        $response = new Response();
        $middleware = new Authorization($this->auth, $this->acl);

        $this->auth->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue($hasIdentity));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        // ROUTE
        $route = new Route([$requestMethod], $pattern, function ($req, $res, $args) {});
        $request = $request->withAttribute('route', $route);

        $next = function ($req, $res) {
            return $res;
        };
        $response = $middleware($request, $response, $next);

        $this->assertEquals($httpStatus, $response->getStatusCode());
        $this->assertEquals($location, $response->getHeaderLine('Location'));
    }

    public function testNullRouteDoesNotAttemptAuth()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/does-not-exist-in-app',
        ]);

        $request = Request::createFromEnvironment($env);
        $response = new Response();
        $middleware = new Authorization($this->auth, $this->acl);

        $this->auth->expects($this->never())
            ->method('hasIdentity');

        $this->auth->expects($this->never())
            ->method('getIdentity');

        $next = function ($req, $res) {
            return $res;
        };
        $response = $middleware($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getHeaderLine('Location'));
    }

    public function authenticationDataProvider()
    {
        /*
        $requestMethod,
        $path,
        $location,
        $hasIdentity,
        $identity,
        $httpStatus,
        $pattern
         */
        return [
            // Guest
            ['GET', '/', null, false, null, 200, '/'],
            ['GET', '/login', null, false, null, 200, '/login'],
            ['POST', '/login', null, false, null, 200, '/login'],
            ['GET', '/member', null, false, null, 401, '/member'],
            // Member
            ['GET', '/admin', null, true, new Identity('member'), 403, '/admin'],
            ['DELETE', '/member/photo/992892', null, true, ['role' => 'member'], 200, '/member/photo/{id}'],
            // Admin
            ['GET', '/admin', null, true, ['role' => 'admin'], 200, '/admin'],
        ];
    }

    private function getConfiguredAcl()
    {
        $acl = new Acl();

        $acl->addRole(new Role('guest'));
        $acl->addRole(new Role('member'), 'guest');
        $acl->addRole(new Role('admin'));

        $acl->addResource('/');
        $acl->addResource('/login');
        $acl->addResource('/member');
        $acl->addResource('/member/photo/{id}');
        $acl->addResource('/admin');

        $acl->allow('guest', '/');
        $acl->allow('guest', '/login', ['GET', 'POST']);

        $acl->allow('member', '/member');
        $acl->allow('member', '/member/photo/{id}', 'DELETE');

        // admin gets everything
        $acl->allow('admin');

        return $acl;
    }
}

final class Identity implements \JeremyKendall\Slim\Auth\IdentityInterface
{
    private $identity;

    public function __construct($identity)
    {
        $this->identity = $identity;
    }

    public function getRole()
    {
        return $this->identity;
    }
}
