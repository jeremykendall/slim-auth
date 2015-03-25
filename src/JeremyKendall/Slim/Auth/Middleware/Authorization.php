<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2015 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth\Middleware;

use JeremyKendall\Slim\Auth\Exception\HttpForbiddenException;
use JeremyKendall\Slim\Auth\Exception\HttpUnauthorizedException;
use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Permissions\Acl\AclInterface;

/**
 * Authorization middleware: Checks user's authorization to access the
 * requested URI.
 *
 * Will redirect a guest to name login route if they attempt to visit a
 * secured URI.
 *
 * Returns HTTP 403 if authenticated user visits a URI they are not
 * authorized for.
 */
class Authorization extends \Slim\Middleware
{
    /**
     * Authentication service.
     *
     * @var AuthenticationServiceInterface
     */
    private $auth;

    /**
     * AclInterface.
     *
     * @var AclInterface
     */
    private $acl;

    /**
     * Public constructor.
     *
     * @param AuthenticationServiceInterface $auth Authentication service
     * @param AclInterface                   $acl  Zend AclInterface
     */
    public function __construct(AuthenticationServiceInterface $auth, AclInterface $acl)
    {
        $this->auth = $auth;
        $this->acl = $acl;
    }

    /**
     * Uses hook to check for user authorization.
     * Will redirect to named login route if user is unauthorized.
     *
     * @throws HttpForbiddenException    If an authenticated user is not authorized for the resource
     * @throws HttpUnauthorizedException If an unauthenticated user is not authorized for the resource
     */
    public function call()
    {
        $app = $this->app;
        $auth = $this->auth;
        $acl = $this->acl;
        $role = $this->getRole($auth->getIdentity());

        $isAuthorized = function () use ($app, $auth, $acl, $role) {
            $resource = $app->router->getCurrentRoute()->getPattern();
            $privilege = $app->request->getMethod();
            $hasIdentity = $auth->hasIdentity();
            $isAllowed = $acl->isAllowed($role, $resource, $privilege);

            if ($hasIdentity && !$isAllowed) {
                throw new HttpForbiddenException();
            }

            if (!$hasIdentity && !$isAllowed) {
                throw new HttpUnauthorizedException();
            }
        };

        $app->hook('slim.before.dispatch', $isAuthorized);

        $this->next->call();
    }

    /**
     * Gets role from user's identity.
     *
     * @param mixed $identity User's identity. If null, returns role 'guest'
     *
     * @return string User's role
     */
    private function getRole($identity = null)
    {
        $role = null;

        if (is_object($identity)) {
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
