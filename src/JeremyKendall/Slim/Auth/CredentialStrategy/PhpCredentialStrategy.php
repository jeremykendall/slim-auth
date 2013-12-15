<?php

/**
 * Slim Auth
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 * @copyright Copyright (c) 2013 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */
namespace JeremyKendall\Slim\Auth\CredentialStrategy;

use JeremyKendall\Slim\Auth\CredentialStrategy\CredentialStrategyInterface;

/**
 * Credential strategy using PHP's native hashing library
 *
 * If PHP version < 5.5, uses password_compat
 * @link https://github.com/ircmaxell/password_compat password_compat
 */
class PhpCredentialStrategy implements CredentialStrategyInterface
{
    /**
     * @var int The algorithm to use (Defined by PASSWORD_* constants)
     */
    private $algo;

    /**
     * @var array The options for the algorithm to use
     */
    private $options;

    /**
     * Public constructor
     *
     * @param int   $algo    The algorithm to use (Defined by PASSWORD_* constants)
     * @param array $options OPTIONAL The options for the algorithm to use
     */
    public function __construct($algo, array $options = array())
    {
        $this->algo = $algo;
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function hashPassword($password)
    {
        return password_hash($password, $this->algo, $this->options);
    }

    /**
     * {@inheritDoc}
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param  string  $hash The hash to test
     * @return boolean True if the password needs to be rehashed.
     */
    public function needsRehash($password)
    {
        return password_needs_rehash($password, $this->algo, $this->options);
    }
}
