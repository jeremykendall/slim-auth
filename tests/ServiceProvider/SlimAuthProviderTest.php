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
    public function testCanProvideServicesToSlimContainer()
    {
        $container = $this->getSlimContainer();
        $container->register($this->provider);

        $this->assertInstanceOf('Zend\Permissions\Acl\AclInterface', $container->get('acl'));
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
        $container = $this->getSlimContainer();
        // Implementor's auth adapter
        $container['authAdapter'] = function ($c) {
            return $this->getMockBuilder('Zend\Authentication\Adapter\AbstractAdapter')
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        };
        $container->register($this->provider);

        // AuthenticationService provided by SlimAuthProvider
        $authenticationService = $container->get('auth');

        $this->assertInstanceOf(
            'Zend\Authentication\AuthenticationServiceInterface',
            $authenticationService
        );
        // Auth adapter provided by implementor's container
        $this->assertInstanceOf(
            'Zend\Authentication\Adapter\AdapterInterface',
            $authenticationService->getAdapter()
        );
        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Middleware\Authorization',
            $container->get('slimAuthAuthorizationMiddleware')
        );
    }

    /**
     * @return \Slim\Container Default Slim container
     */
    private function getSlimContainer()
    {
        $container = new \Slim\Container();

        return $container;
    }
}
