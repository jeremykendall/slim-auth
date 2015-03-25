<?php

namespace JeremyKendall\Slim\Auth\Tests\Middleware;

use JeremyKendall\Slim\Auth\Exception\AuthException;
use JeremyKendall\Slim\Auth\Middleware\Authorization;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;

class AuthorizationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Zend\Authentication\AuthenticationService
     */
    private $auth;

    /**
     * @var Zend\Permissions\Acl\Acl
     */
    private $acl;

    /**
     * @var Authorization
     */
    private $middleware;

    protected function setUp()
    {
        parent::setUp();
        $this->auth = $this->getMock('Zend\Authentication\AuthenticationService');
        $this->acl = $this->getConfiguredAcl();
        $this->middleware = new Authorization($this->auth, $this->acl);
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
        $httpStatus
    ) {
        \Slim\Environment::mock(array(
            'REQUEST_METHOD' => $requestMethod,
            'PATH_INFO' => $path,
        ));

        $this->auth->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue($hasIdentity));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $app = new \Slim\Slim(array('debug' => false));

        $app->error(function (\Exception $e) use ($app) {
            // Example of handling Auth Exceptions
            if ($e instanceof AuthException) {
                $app->response->setStatus($e->getCode());
                $app->response->setBody($e->getMessage());
            }
        });
        $app->get('/', function () {});
        $app->get('/member', function () {});
        $app->delete('/member/photo/:id', function ($id) {});
        $app->get('/admin', function () {});
        $app->map('/login', function () {})
            ->via('GET', 'POST')
            ->name('login');
        $app->add($this->middleware);
        ob_start();
        $app->run();
        ob_end_clean();

        $this->assertEquals($httpStatus, $app->response->status());
        $this->assertEquals($location, $app->response->header('location'));
    }

    public function authenticationDataProvider()
    {
        /*
        $requestMethod,
        $path,
        $location,
        $hasIdentity,
        $identity,
        $httpStatus
         */
        return array(
            // Guest
            array('GET', '/', null, false, null, 200),
            array('GET', '/login', null, false, null, 200),
            array('POST', '/login', null, false, null, 200),
            array('GET', '/member', null, false, null, 401),
            // Member
            array('GET', '/admin', null, true, new Identity('member'), 403),
            array('DELETE', '/member/photo/992892', null, true, array('role' => 'member'), 200),
            // Admin
            array('GET', '/admin', null, true, array('role' => 'admin'), 200),
        );
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
        $acl->addResource('/member/photo/:id');
        $acl->addResource('/admin');

        $acl->allow('guest', '/');
        $acl->allow('guest', '/login', array('GET', 'POST'));
        $acl->deny('guest', '/admin');

        $acl->allow('member', '/member');
        $acl->allow('member', '/member/photo/:id', 'DELETE');

        // admin gets everything
        $acl->allow('admin');

        return $acl;
    }
}

class Identity implements \JeremyKendall\Slim\Auth\IdentityInterface
{
    protected $identity;

    public function __construct($identity)
    {
        $this->identity = $identity;
    }

    public function getRole()
    {
        return $this->identity;
    }
}
