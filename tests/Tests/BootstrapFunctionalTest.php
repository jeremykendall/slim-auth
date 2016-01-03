<?php

namespace JeremyKendall\Slim\Auth\Tests;

use JeremyKendall\Slim\Auth\Bootstrap;
use Slim\Slim;
use Zend\Permissions\Acl\Acl;

/**
 * @group functional
 */
class BootstrapFunctionalTest extends \PHPUnit_Framework_TestCase
{
    private $bootstrap;
    private $app;
    private $adapter;
    private $acl;

    /**
     * Confirms that $this->app->auth and $this->app->authenticator
     * return the expected class instances.
     */
    public function testBootstrap()
    {
        $app = new Slim();
        $adapter = $this->getMockBuilder('Zend\Authentication\Adapter\AbstractAdapter')
            ->disableOriginalConstructor()
            ->getMock();
        $acl = new Acl();

        $bootstrap = new Bootstrap($app, $adapter, $acl);
        $bootstrap->bootstrap();

        $this->assertInstanceOf(
            'JeremyKendall\Slim\Auth\Authenticator',
            $app->authenticator
        );
    }
}
