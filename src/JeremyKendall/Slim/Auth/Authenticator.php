<?php

/**
 * Slim Auth
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 * @copyright Copyright (c) 2013 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth;

use Zend\Authentication\AuthenticationService;

/**
 * Authenticates users
 */
class Authenticator
{
    /**
     * @var AuthenticationService
     */
    private $auth;

    /**
     * Public constructor
     *
     * @param AuthenticationService $auth
     */
    public function __construct(AuthenticationService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Authenticates user
     *
     * @param  string                                         $identity   User identifier (username, email, etc)
     * @param  string                                         $credential User password
     * @return Zend\Authentication\Result
     * @throws Zend\Authentication\Exception\RuntimeException
     */
    public function authenticate($identity, $credential)
    {
        $adapter = $this->auth->getAdapter();
        $adapter->setIdentity($identity);
        $adapter->setCredential($credential);

        return $this->auth->authenticate();
    }
}
