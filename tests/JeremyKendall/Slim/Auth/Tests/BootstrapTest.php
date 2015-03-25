<?php

namespace JeremyKendall\Slim\Auth\Tests;

use JeremyKendall\Slim\Auth\Bootstrap;
use Slim\Slim;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Permissions\Acl\Acl;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    private $bootstrap;
    private $app;
    private $adapter;
    private $acl;

    protected function setUp()
    {
        parent::setUp();
        $this->getBootstrap();
    }

    protected function tearDown()
    {
        $this->bootstrap = null;
    }

    public function testConstructorSetsDefaults()
    {
        $this->assertSame($this->adapter, $this->bootstrap->getAdapter());
        $this->assertSame($this->acl, $this->bootstrap->getAcl());
    }

    public function testBootstrap()
    {
        $authMiddleware = $this
            ->getMockBuilder('JeremyKendall\Slim\Auth\Middleware\Authorization')
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->expects($this->exactly(2))
            ->method('__set')
            ->withConsecutive(
                array('auth', $this->anything()),
                array('authenticator', $this->anything())
            );

        $this->app->expects($this->once())
            ->method('add')
            ->with($authMiddleware);

        $this->bootstrap->setAuthMiddleware($authMiddleware);
        $this->bootstrap->bootstrap();
    }

    public function testGetSetStorage()
    {
        $defaultStorage = $this->bootstrap->getStorage();

        $this->assertInstanceOf(
            'Zend\Authentication\Storage\StorageInterface',
            $defaultStorage
        );
        $this->assertEquals('slim_auth', $defaultStorage->getNamespace());

        $storage = $this->getMock('Zend\Authentication\Storage\StorageInterface');
        $this->bootstrap->setStorage($storage);

        $this->assertSame($storage, $this->bootstrap->getStorage());
    }

    public function testGetDefaultMiddleware()
    {
        $auth = $this->getMockBuilder('Zend\Authentication\AuthenticationServiceInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->expects($this->once())
            ->method('__get')
            ->with('auth')
            ->will($this->returnValue($auth));

        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Middleware\Authorization',
            $this->bootstrap->getAuthMiddleware()
        );
    }

    private function getBootstrap(StorageInterface $storage = null)
    {
        $this->app = $this->getMockBuilder('Slim\Slim')
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter = $this->getMockBuilder('Zend\Authentication\Adapter\AbstractAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->acl = new Acl();

        $this->bootstrap = new Bootstrap($this->app, $this->adapter, $this->acl);

        if ($storage !== null) {
            $this->bootstrap->setStorage($storage);
        }
    }
}
