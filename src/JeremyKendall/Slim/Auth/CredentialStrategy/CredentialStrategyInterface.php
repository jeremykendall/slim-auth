<?php

/**
 * Slim Auth
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 * @copyright Copyright (c) 2013 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */
namespace JeremyKendall\Slim\Auth\CredentialStrategy;

/**
 * Interface for CredentialStrategy classes
 */
interface CredentialStrategyInterface
{
    /**
     * Creates a new password hash using a strong one-way hashing algorithm
     *
     * @param  string $password Plain-text password
     * @return string Password hash
     */
    public function hashPassword($password);

    /**
     * Verifies that the given hash matches the given password
     *
     * @param  string $password Plain-text password
     * @param  string $hash     Password hash
     * @return bool   TRUE if the password and hash match, FALSE otherwise.
     */
    public function verifyPassword($password, $hash);
}
