<?php

namespace JeremyKendall\Slim\Auth\CredentialStrategy;

use JeremyKendall\Slim\Auth\CredentialStrategy\CredentialStrategyInterface;
use PasswordHash;

class PHPassCredentialStrategy implements CredentialStrategyInterface
{
    /**
     * @var PasswordHash
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
