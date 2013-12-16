<?php

namespace JeremyKendall\Slim\Auth\Tests\CredentialStrategy;

use JeremyKendall\Slim\Auth\CredentialStrategy\PhpCredentialStrategy;

/**
 * The tests below were taken from Anthony Ferrara's password_compat test suite
 * @see https://github.com/ircmaxell/password_compat
 */
class PhpCredentialStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PhpCredentialStrategy
     */
    private $strategy;

    protected function setUp()
    {
        parent::setUp();
        $this->strategy = new PhpCredentialStrategy(PASSWORD_BCRYPT);
    }

    public function testFunctionsExist()
    {
        $this->assertTrue(function_exists('password_hash'));
        $this->assertTrue(function_exists('password_verify'));
        $this->assertTrue(function_exists('password_needs_rehash'));
    }

    public function testHashPassword()
    {
        $strategy = new PhpCredentialStrategy(PASSWORD_BCRYPT);
        $hash = $strategy->hashPassword('foo');
        $this->assertEquals($hash, crypt('foo', $hash));
    }

    public function testKnownSalt()
    {
        $strategy = new PhpCredentialStrategy(
            PASSWORD_BCRYPT,
            array("cost" => 7, "salt" => "usesomesillystringforsalt")
        );
        $hash = $strategy->hashPassword("rasmuslerdorf");

        $this->assertEquals(
            '$2y$07$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi',
            $hash
        );
    }

    public function testFailedType()
    {
        $this->assertFalse($this->strategy->verifyPassword(123, 123));
    }

    public function testSaltOnly()
    {
        $this->assertFalse($this->strategy->verifyPassword('foo', '$2a$07$usesomesillystringforsalt$'));
    }

    public function testInvalidPassword()
    {
        $this->assertFalse($this->strategy->verifyPassword(
            'rasmusler',
            '$2a$07$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi'
        ));
    }

    // Needs to be rehashed
    public function testValidPassword()
    {
        $this->assertTrue($this->strategy->verifyPassword(
            'rasmuslerdorf',
            '$2a$07$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi'
        ));
    }

    /**
     * @dataProvider provideCases
     */
    public function testCases($hash, $algo, $options, $valid)
    {
        $strategy = new PhpCredentialStrategy($algo, $options);
        $this->assertEquals($valid, $strategy->needsRehash($hash));
    }

    public static function provideCases()
    {
        return array(
            array('foo', 0, array(), false),
            array('foo', 1, array(), true),
            array(
                '$2y$07$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi', 
                PASSWORD_BCRYPT, 
                array(), 
                true
            ),
            array(
                '$2y$07$usesomesillystringfore2udlvp1ii2e./u9c8sbjqp8i90dh6hi', 
                PASSWORD_BCRYPT, 
                array('cost' => 7), 
                false
            ),
            array(
                '$2y$07$usesomesillystringfore2udlvp1ii2e./u9c8sbjqp8i90dh6hi', 
                PASSWORD_BCRYPT, 
                array('cost' => 5), 
                true
            ),
        );
    }
}
