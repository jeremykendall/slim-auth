<?php

namespace JeremyKendall\Slim\Auth\Tests;

use JeremyKendall\Slim\Auth\Bootstrap;
use Slim\Slim;
use Zend\Authentication\AuthenticationService;
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

        $this->app->expects($this->once())
            ->method('add')
            ->with($authMiddleware);

        $this->bootstrap->setAuthMiddleware($authMiddleware);
        $this->bootstrap->bootstrap();

        $this->assertInstanceOf(
            'Closure',
            $this->app->auth
        );

        $this->assertInstanceOf(
            'Closure',
            $this->app->authenticator
        );
    }

    public function testGetSetStorage()
    {
        $storage = $this->getMock('Zend\Authentication\Storage\StorageInterface');

        $this->assertNull($this->bootstrap->getStorage());
        $this->bootstrap->setStorage($storage);
        $this->assertSame($storage, $this->bootstrap->getStorage());
    }

    public function testGetDefaultMiddleware()
    {
        $auth = $this->getMockBuilder('Zend\Authentication\AuthenticationService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->auth = $auth;

        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Middleware\Authorization', 
            $this->bootstrap->getAuthMiddleware()
        );
    }

    private function getBootstrap(StorageInterface $storage = null)
    {
        $this->app = $this->getMockBuilder('Slim\Slim')
            ->disableOriginalConstructor()
            ->setMethods(array('add'))
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
