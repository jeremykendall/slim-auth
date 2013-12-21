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
        $this->bootstrap->bootstrap();
        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Authenticator', 
            $this->app->authenticator
        );
        $this->assertInstanceOf(
            'Zend\Authentication\Adapter\AbstractAdapter', 
            $this->bootstrap->getAdapter()
        );
    }

    public function testGetSetStorage()
    {
        $this->assertInstanceOf(
            'Zend\Authentication\Storage\Session', 
            $this->bootstrap->getStorage()
        );

        $this->bootstrap->bootstrap();

        $this->assertInstanceOf(
            'Zend\Authentication\Storage\Session', 
            $this->app->auth->getStorage()
        );

        $this->assertInstanceOf(
            'Zend\Authentication\Storage\Session', 
            $this->app->auth->getStorage()
        );

        $this->bootstrap->setStorage($this->getMock('Zend\Authentication\Storage\Chain'));
        $this->bootstrap->bootstrap();
        $this->assertInstanceOf(
            'Zend\Authentication\Storage\Chain', 
            $this->app->auth->getStorage()
        );
    }

    public function testAuthenticationServiceConfiguredProperly()
    {
        $this->bootstrap->bootstrap();

        $this->assertInstanceOf(
            'Zend\Authentication\Storage\Session', 
            $this->app->auth->getStorage()
        );
        $this->assertSame($this->adapter, $this->app->auth->getAdapter());
    }

    private function getBootstrap(StorageInterface $storage = null)
    {
        $this->app = new Slim();
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
