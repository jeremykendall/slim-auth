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

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(null));

        $app = new \Slim\Slim();
        $app->get('/', function () {});
        $app->get('/login', function () {})->name('login');
        $app->add($this->middleware);
        $app->run();

        $response = $app->response();

        $this->assertTrue($response->isOk());
    }

    public function testVisitPhotosPageWithRouteParamsSucceeds()
    {
        \Slim\Environment::mock(array(
            'PATH_INFO' => '/photos/5893058038530'
        ));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(null));

        $app = new \Slim\Slim();
        $app->get('/photos/:photoId', function ($photoId) {});
        $app->get('/login', function () {})->name('login');
        $app->add($this->middleware);
        $app->run();

        $response = $app->response();

        $this->assertTrue($response->isOk());
    }

    public function testVisitAdminPageNotLoggedInRedirectsToLogin()
    {
        \Slim\Environment::mock(array(
            'PATH_INFO' => '/admin'
        ));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(null));

        $app = new \Slim\Slim();
        $app->get('/admin', function () {});
        $app->get('/login', function () {})->name('login');
        $app->add($this->middleware);
        $app->run();

        $response = $app->response();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(302, $response->status());
        $this->assertEquals('/login', $response->header('location'));
    }

    public function testPermissionsWhichIncludePrivileges()
    {
        $this->markTestSkipped(
            "Haven't sorted this out yet. Seems there's '
            . 'no simple way to use privileges."
        );

        \Slim\Environment::mock(array(
            'PATH_INFO' => '/member/avatar'
        ));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(array('role' => 'member')));

        $app = new \Slim\Slim();
        $app->get('/member/avatar', function () {});
        $app->get('/login', function () {})->name('login');
        $app->add($this->middleware);
        $app->run();

        $response = $app->response();

        $this->assertTrue($response->isOk());
    }

    public function testRouteWithOptionalParamsNotProvided()
    {
        \Slim\Environment::mock(array(
            'PATH_INFO' => '/member/photos'
        ));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(array('role' => 'member')));

        $app = new \Slim\Slim();
        $app->get('/member/photos(/:page)', function () {});
        $app->get('/login', function () {})->name('login');
        $app->add($this->middleware);
        $app->run();

        $response = $app->response();

        $this->assertTrue($response->isOk());
    }

    public function testAuthenticatedMemberVisitDeniedResourceReturnsThrowsException()
    {
        $this->setExpectedException(
            'JeremyKendall\Slim\Auth\Exception\HttpForbiddenException',
            'You are not authorized to access this resource',
            403
        );

        \Slim\Environment::mock(array(
            'PATH_INFO' => '/admin'
        ));

        $this->auth->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(array('role' => 'member')));

        $app = new \Slim\Slim(array('debug' => false));
        $app->get('/admin', function () {});
        $app->get('/login', function () {})->name('login');
        $app->add($this->middleware);
        // Ensure exception gets thrown in a manner I can test here
        $app->error(function(\Exception $e) {
            throw $e;
        });
        $app->run();
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

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($identity));

        $app = new \Slim\Slim();
        $app->get('/admin', function () {});
        $app->get('/login', function () {})->name('login');
        $app->add($this->middleware);
        $app->run();

        $response = $app->response();

        $this->assertTrue($response->isOk());
    }

    public function testVisitAdminSettingsPageLoggedInSucceeds()
    {
        \Slim\Environment::mock(array(
            'PATH_INFO' => '/admin/settings'
        ));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(array('role' => 'admin')));

        $app = new \Slim\Slim();
        $app->get('/admin/settings', function () {});
        $app->get('/login', function () {})->name('login');
        $app->add($this->middleware);
        $app->run();

        $response = $app->response();

        $this->assertTrue($response->isOk());
    }

    public function testAdminPermissions()
    {
        $this->assertTrue($this->acl->isAllowed('admin', '/admin'));
        $this->assertTrue($this->acl->isAllowed('admin', '/admin/settings'));
        //$this->assertTrue($this->acl->isAllowed('admin', '/admin/users'));
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
        $acl->addResource('/login');
        $acl->addResource('/logout');
        $acl->addResource('/member');
        $acl->addResource('/member/photos(/:page)');
        $acl->addResource('/photos/:photoId');

        // Admin resources
        $acl->addResource('/admin');
        $acl->addResource('/admin/settings');

        $acl->allow('guest', '/');
        $acl->allow('guest', '/login');
        $acl->allow('guest', '/photos/:photoId');
        $acl->deny('guest', '/admin');

        $acl->allow('member', '/logout');
        $acl->allow('member', '/member/photos(/:page)');
        $acl->deny('member', '/admin');

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
