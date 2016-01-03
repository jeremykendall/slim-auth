<?php

namespace JeremyKendall\Slim\Auth\Tests;

use JeremyKendall\Slim\Auth\Authenticator;
use Zend\Authentication\Result;

class AuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * @var AuthenticationService
     */
    private $auth;

    /**
     * @var AbstractAdapter
     */
    private $adapter;

    protected function setUp()
    {
        parent::setUp();
        $this->auth = $this->getMockBuilder('Zend\Authentication\AuthenticationService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapter = $this->getMockBuilder('Zend\Authentication\Adapter\AbstractAdapter')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->authenticator = new Authenticator($this->auth);
    }

    public function testAuthenticationSucceeds()
    {
        $email = 'user@example.com';
        $password = 12345678;

        $this->auth->expects($this->once())
            ->method('getAdapter')
            ->will($this->returnValue($this->adapter));

        $this->auth->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($this->getSuccessResult()));

        $result = $this->authenticator->authenticate($email, $password);

        // Ensures identity and credential were actually set on the mock adapter
        $this->assertEquals($email, $this->adapter->getIdentity());
        $this->assertEquals($password, $this->adapter->getCredential());

        $this->assertTrue($result->isValid());
    }

    public function testAuthenticationFails()
    {
        $email = 'user@example.com';
        $password = 12345678;

        $this->auth->expects($this->once())
            ->method('getAdapter')
            ->will($this->returnValue($this->adapter));

        $this->auth->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($this->getFailureResult()));

        $result = $this->authenticator->authenticate($email, $password);

        // Ensures identity and credential were actually set on the mock adapter
        $this->assertEquals($email, $this->adapter->getIdentity());
        $this->assertEquals($password, $this->adapter->getCredential());

        $this->assertFalse($result->isValid());
    }

    public function testLogout()
    {
        $this->auth->expects($this->once())
            ->method('clearIdentity');

        $this->authenticator->logout();
    }

    private function getFailureResult()
    {
        return new Result(Result::FAILURE, array(), array('FAILZORS'));
    }

    private function getSuccessResult()
    {
        return new Result(Result::SUCCESS, array(), array('YOU DEEED EEET!'));
    }
}
