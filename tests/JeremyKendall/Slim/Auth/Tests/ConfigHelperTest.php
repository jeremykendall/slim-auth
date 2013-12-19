<?php

namespace JeremyKendall\Slim\Auth\Tests;

use JeremyKendall\Slim\Auth\ConfigHelper;
use Slim\Slim;
use Zend\Permissions\Acl\Acl;

class ConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    private $configHelper;

    protected function setUp()
    {
        parent::setUp();

        $app = new Slim();
        $this->adapter = $this->getMockBuilder('Zend\Authentication\Adapter\AbstractAdapter')
            ->disableOriginalConstructor()
            ->getMock();
        $acl = new Acl();

        $this->configHelper = new ConfigHelper($app, $this->adapter, $acl);
    }

    protected function tearDown()
    {
        $this->configHelper = null;
    }

    public function testConstructorSetsDependencies()
    {
        $app = new Slim();
        $acl = new Acl();

        $configHelper = new ConfigHelper($app, $this->adapter, $acl);

        $this->assertSame($app, $configHelper->getApp());
        $this->assertSame($this->adapter, $configHelper->getAdapter());
        $this->assertSame($acl, $configHelper->getAcl());
    }

    public function testCreate()
    {
        $this->configHelper->create();
        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Authenticator', 
            $this->configHelper->getApp()->authenticator
        );
        $this->assertInstanceOf(
            'Zend\Authentication\Adapter\AbstractAdapter', 
            $this->configHelper->getAdapter()
        );
    }

    public function testGetSetCredentialStrategy()
    {
        $this->assertNull($this->configHelper->getCredentialStrategy());
        $phpass = $this->getMockBuilder('JeremyKendall\Slim\Auth\CredentialStrategy\PHPassCredentialStrategy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configHelper->setCredentialStrategy($phpass);

        $this->assertSame($phpass, $this->configHelper->getCredentialStrategy());
    }

    public function testGetSetStorage()
    {
        $this->assertNull($this->configHelper->getStorage());
        $storage = $this->getMock('Zend\Authentication\Storage\StorageInterface');
        $this->configHelper->setStorage($storage);
        $this->assertSame($storage, $this->configHelper->getStorage());
    }

    public function testAuthenticationServiceConfiguredProperly()
    {
        $this->configHelper->create();

        $this->assertInstanceOf(
            'Zend\Authentication\Storage\Session', 
            $this->configHelper->getAuth()->getStorage()
        );
        $this->assertSame($this->adapter, $this->configHelper->getAuth()->getAdapter());
    }
}
