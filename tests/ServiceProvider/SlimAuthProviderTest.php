<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2013-2016 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */
namespace JeremyKendall\Slim\Auth\Tests\ServiceProvider;

use JeremyKendall\Slim\Auth\ServiceProvider\SlimAuthProvider;
use Slim\Http\Response;

class SlimAuthProviderTest extends \PHPUnit_Framework_TestCase
{
    private $provider;

    protected function setUp()
    {
        parent::setUp();
        $this->provider = new SlimAuthProvider();
    }

    protected function tearDown()
    {
        $this->provider = null;
        parent::tearDown();
    }

    public function testImplements()
    {
        $this->assertInstanceOf('Pimple\ServiceProviderInterface', $this->provider);
    }

    /**
     * The \JeremyKendall\Slim\Auth\ServiceProvider\SlimAuthProvider should be
     * able to provide services to the \Slim\Container.
     */
    public function testCanProvideSlimAuthServicesToSlimContainer()
    {
        $container = $this->getSlimContainer();
        $container->register($this->provider);

        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Handlers\RedirectHandler',
            $container->get('redirectHandler')
        );
        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Handlers\ThrowHttpExceptionHandler',
            $container->get('throwHttpExceptionHandler')
        );
    }

    /**
     * Since Slim Auth can't know anything about an implementor's auth adapter,
     * it's necessary that the implementor's \Slim\Container provide the auth
     * adapter to the Slim Auth AuthenticationService. This test is to confirm
     * that works as expected.
     */
    public function testSlimContainerCanProvideAuthAdapterToProviderAuthService()
    {
        $container = $this->getSlimContainerWithAuthenticationServiceAndAcl();
        $container->register($this->provider);

        // Auth adapter provided by implementor's container
        $this->assertInstanceOf(
            'Zend\Authentication\Adapter\AdapterInterface',
            $container->get('auth')->getAdapter()
        );
    }

    public function testSlimAuthServicesExistOnSlimContainer()
    {
        $container = $this->getSlimContainerWithAuthenticationServiceAndAcl();
        $container->register($this->provider);

        $authenticationService = $container->get('auth');

        $this->assertInstanceOf(
            'Zend\Authentication\AuthenticationServiceInterface',
            $authenticationService
        );

        // Middleware using RedirectHandler
        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Middleware\Authorization',
            $container->get('slimAuthRedirectMiddleware')
        );
        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Handlers\RedirectHandler',
            $container->get('slimAuthRedirectMiddleware')->getHandler()
        );

        // Middleware using ThrowHttpExceptionHandler
        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Middleware\Authorization',
            $container->get('slimAuthThrowHttpExceptionMiddleware')
        );
        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Handlers\ThrowHttpExceptionHandler',
            $container->get('slimAuthThrowHttpExceptionMiddleware')->getHandler()
        );

        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Authenticator',
            $container->get('authenticator')
        );
    }

    public function testCanOverrideAuthStorage()
    {
        $container = $this->getSlimContainerWithAuthenticationServiceAndAcl();
        $storageInterface = $this->getMockBuilder('Zend\Authentication\Storage\StorageInterface')
            ->getMock();
        $container['authStorage'] = $storageInterface;
        $container->register($this->provider);

        $this->assertSame($storageInterface, $container->get('auth')->getStorage());
    }

    public function testCanOverrideRedirectHandlerArgs()
    {
        $redirectNotAuthenticated = '/alternate-login';
        $redirectNotAuthorized = '/you-shall-not-pass';

        $container = $this->getSlimContainerWithAuthenticationServiceAndAcl();
        $container['redirectNotAuthenticated'] = '/alternate-login';
        $container['redirectNotAuthorized'] = '/you-shall-not-pass';
        $container->register($this->provider);

        $redirect = $container->get('redirectHandler');

        $notAuthenticated = $redirect->notAuthenticated(new Response());
        $this->assertEquals($redirectNotAuthenticated, $notAuthenticated->getHeaderLine('Location'));

        $notAuthorized = $redirect->notAuthorized(new Response());
        $this->assertEquals($redirectNotAuthorized, $notAuthorized->getHeaderLine('Location'));
    }

    /**
     * @return \Slim\Container Default Slim container
     */
    private function getSlimContainer()
    {
        $container = new \Slim\Container();

        return $container;
    }

    private function getSlimContainerWithAuthenticationServiceAndAcl()
    {
        $container = $this->getSlimContainer();
        // Implementor's auth adapter
        $container['authAdapter'] = function ($c) {
            return $this->getMockBuilder('Zend\Authentication\Adapter\AbstractAdapter')
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        };
        $container['acl'] = function ($c) {
            return $this->getMockBuilder('Zend\Permissions\Acl\AclInterface')
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        };

        return $container;
    }
}
