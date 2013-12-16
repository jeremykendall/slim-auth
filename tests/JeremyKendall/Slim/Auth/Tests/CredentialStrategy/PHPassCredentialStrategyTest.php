<?php

namespace JeremyKendall\Slim\Auth\Tests\CredentialStrategy;

use JeremyKendall\Slim\Auth\CredentialStrategy\PHPassCredentialStrategy;

class PHPassCredentialStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PasswordHash
     */
    private $hasher;

    /**
        * @var PHPassCredentialStrategy
     */
    private $strategy;

    protected function setUp()
    {
        parent::setUp();
        $this->hasher = $this->getMockBuilder('PasswordHash')
            ->disableOriginalConstructor()
            ->getMock();
        $this->strategy = new PHPassCredentialStrategy($this->hasher);
    }

    public function testHashPassword()
    {
        $this->hasher->expects($this->once())
            ->method('HashPassword')
            ->with('password')
            ->will($this->returnValue('passwordHash'));

        $result = $this->strategy->hashPassword('password');
        $this->assertEquals('passwordHash', $result);
    }

    public function testVerifyPassword()
    {
        $this->hasher->expects($this->once())
            ->method('CheckPassword')
            ->with('password', 'hash')
            ->will($this->returnValue(true));

        $result = $this->strategy->verifyPassword('password', 'hash');
        $this->assertTrue($result);
    }
}
