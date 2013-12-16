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
use PasswordHash;

/**
 * Credential strategy using Openwall's PHPass for implementation
 * @link http://www.openwall.com/phpass/ Openwall PHPass
 */
class PHPassCredentialStrategy implements CredentialStrategyInterface
{
    /**
     * @var PasswordHash PHPass hasher
     */
    private $hasher;

    /**
     * Public constructor
     *
     * @param PasswordHash $hasher
     */
    public function __construct(PasswordHash $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * {@inheritDoc}
     */
    public function hashPassword($password)
    {
        return $this->hasher->HashPassword($password);
    }

    /**
     * {@inheritDoc}
     */
    public function verifyPassword($password, $hash)
    {
        return $this->hasher->CheckPassword($password, $hash);
    }
}
