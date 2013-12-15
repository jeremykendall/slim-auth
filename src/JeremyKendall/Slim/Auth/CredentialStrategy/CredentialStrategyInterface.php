<?php

namespace JeremyKendall\Slim\Auth\CredentialStrategy;

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
