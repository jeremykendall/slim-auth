<?php

namespace JeremyKendall\Slim\Auth\Tests\Middleware;

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

    /**
     * @var array Identity
     */
    private $identity;

    protected function setUp()
    {
        parent::setUp();
        $config = array(
            'login.url' => '/login',
            'secured.urls' => array(
                array('path' => '/admin'),
                array('path' => '/admin/.+')
            )
        );
        $this->auth = $this->getMock('Zend\Authentication\AuthenticationService');
        $this->acl = $this->getConfiguredAcl();
        $this->identity = array('role' => 'admin');

        $this->middleware = new Authorization($this->auth, $this->acl);
    }

    protected function tearDown()
    {
        $this->middleware = null;
        parent::tearDown();
    }

    public function testVisitHomePageNotLoggedInSucceeds()
    {
        \Slim\Environment::mock(array(
            'PATH_INFO' => '/'
        ));

        $app = new \Slim\Slim();

        $app->get('/', function () {
            echo 'Success';
        });

        $app->get('/login', function () {})->name('login');

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(null));

        $this->middleware->setApplication($app);
        $this->middleware->setNextMiddleware($app);
        $this->middleware->call();
        $response = $app->response();
        $this->assertTrue($response->isOk());
    }

    public function testVisitAdminPageNotLoggedInRedirectsToLogin()
    {
        \Slim\Environment::mock(array(
            'PATH_INFO' => '/admin'
        ));

        $app = new \Slim\Slim();

        $app->get('/admin', function () {
            echo 'Y U NO LOGGED IN';
        });

        $app->get('/login', function () {})->name('login');

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(null));

        $this->middleware->setApplication($app);
        $this->middleware->setNextMiddleware($app);
        $this->middleware->call();
        $response = $app->response();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals(302, $response->status());
        $this->assertEquals('/login', $response->header('location'));
    }

    public function testAuthenticatedMemberVisitDeniedResourceReturns403()
    {
        \Slim\Environment::mock(array(
            'PATH_INFO' => '/admin'
        ));

        $app = new \Slim\Slim();
        $app->get('/admin', function () {});
        $app->get('/login', function () {})->name('login');

        $this->auth->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(new Identity('member')));

        $this->middleware->setApplication($app);
        $this->middleware->setNextMiddleware($app);
        $this->middleware->call();
        $this->assertEquals(403, $app->response->isForbidden());
    }

    /**
     * Tests with both object identity and array identity
     *
     * @dataProvider identityDataProvider
     */
    public function testVisitAdminPageLoggedInSucceeds($identity)
    {
        \Slim\Environment::mock(array(
            'PATH_INFO' => '/admin'
        ));

        $app = new \Slim\Slim();

        $app->get('/admin', function () {
            echo 'Success';
        });

        $app->get('/login', function () {})->name('login');

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $this->middleware->setApplication($app);
        $this->middleware->setNextMiddleware($app);
        $this->middleware->call();
        $response = $app->response();
        $this->assertTrue($response->isOk());
    }

    public function identityDataProvider()
    {
        return array(
            array(array('role' => 'admin')),
            array(new Identity('admin')),
        );
    }

    public function testNoLoginNamedRouteThrowsException()
    {
        $this->setExpectedException('\RuntimeException', 'Named route not found for name: login');

        \Slim\Environment::mock(array(
            'PATH_INFO' => '/admin'
        ));

        $app = new \Slim\Slim();

        $app->get('/admin', function () {});

        // Login route without ->name('login')
        $app->get('/login', function () {});

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(null));

        $this->middleware->setApplication($app);
        $this->middleware->setNextMiddleware($app);
        $this->middleware->call();
    }

    private function getConfiguredAcl()
    {
        $acl = new Acl();

        $acl->addRole(new Role('guest'));
        $acl->addRole(new Role('member'), 'guest');
        $acl->addRole(new Role('admin'));

        $acl->addResource('/');
        $acl->addResource('/admin');
        $acl->addResource('/login');

        $acl->allow('guest', null, '/');
        $acl->allow('guest', null, '/login');
        $acl->deny('guest', null, '/admin');

        $acl->allow('member', null, '/member/profile');
        $acl->allow('member', null, '/logout');
        $acl->deny('member', null, '/admin');

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
