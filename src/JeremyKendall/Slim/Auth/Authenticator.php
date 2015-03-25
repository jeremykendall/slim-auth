<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2015 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth;

use Zend\Authentication\AuthenticationServiceInterface;

/**
 * Authenticates users.
 */
class Authenticator
{
    /**
     * @var AuthenticationServiceInterface ZF Authentication Service
     */
    private $auth;

    /**
     * Public constructor.
     *
     * @param AuthenticationServiceInterface $auth
     */
    public function __construct(AuthenticationServiceInterface $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Authenticates user.
     *
     * @param string $identity   User identifier (username, email, etc)
     * @param string $credential User password
     *
     * @return Zend\Authentication\Result
     *
     * @throws Zend\Authentication\Exception\RuntimeException
     */
    public function authenticate($identity, $credential)
    {
        $adapter = $this->auth->getAdapter();
        $adapter->setIdentity($identity);
        $adapter->setCredential($credential);

        return $this->auth->authenticate();
    }

    /**
     * Clears the identity from persistent storage.
     */
    public function logout()
    {
        $this->auth->clearIdentity();
    }
}
