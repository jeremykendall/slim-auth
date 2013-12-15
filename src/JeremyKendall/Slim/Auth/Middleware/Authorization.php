<?php

/**
 * Slim Auth
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 * @copyright Copyright (c) 2013 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth\Middleware;

use JeremyKendall\Slim\Auth\Exception\HttpForbiddenException;
use Zend\Authentication\AuthenticationService;
use Zend\Permissions\Acl\Acl;

/**
 * Authorization middleware
 *
 * Checks if user is authorized to access the requested URI.
 *
 * Will redirect a guest to name login route if they attempt to visit a secured URI.
 * Returns HTTP 403 if authenticated user visits a URI they are not authorized for.
 */
class Authorization extends \Slim\Middleware
{
    /**
     * Authentication service
     *
     * @var AuthenticationService
     */
    private $auth;

    /**
     * ACL
     *
     * @var Acl
     */
    private $acl;

    /**
     * Public constructor
     *
     * @param AuthenticationService $auth Authentication service
     * @param Acl                   $acl  Zend Acl
     */
    public function __construct(AuthenticationService $auth, Acl $acl)
    {
        $this->auth = $auth;
        $this->acl = $acl;
    }

    /**
     * Uses Slim's 'slim.before.router' hook to check for user authorization.
     * Will redirect to named login route if user is unauthorized
     *
     * @throws \RuntimeException if there isn't a named 'login' route
     */
    public function call()
    {
        $app = $this->app;
        $auth = $this->auth;
        $acl = $this->acl;
        $role = $this->getRole($auth->getIdentity());

        $isAuthorized = function () use ($app, $auth, $acl, $role) {
            $currentRoute = $app->router->getCurrentRoute();
            $isAllowed = $acl->isAllowed($role, $currentRoute->getPattern());
            $hasIdentity = $auth->hasIdentity();

            if ($hasIdentity && !$isAllowed) {
                throw new HttpForbiddenException();
            }

            if (!$hasIdentity && !$isAllowed) {
                return $app->redirect($app->urlFor('login'));
            }
        };

        $app->hook('slim.before.dispatch', $isAuthorized);

        $this->next->call();
    }

    /**
     * Gets role from user's identity.
     *
     * @param  mixed  $identity User's identity. If null, returns role 'guest'
     * @return string User's role
     */
    private function getRole($identity = null)
    {
        $role = null;

        if (is_object($identity)) {
            // TODO: check for IdentityInterface (?)
            $role = $identity->getRole();
        }

        if (is_array($identity) && isset($identity['role'])) {
            $role = $identity['role'];
        }

        if (!$role) {
            $role = 'guest';
        }

        return $role;
    }
}
