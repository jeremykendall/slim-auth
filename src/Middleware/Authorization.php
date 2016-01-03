<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2016 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */
namespace JeremyKendall\Slim\Auth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Permissions\Acl\AclInterface;

/**
 * Authorization middleware: Checks user's authorization to access the
 * requested URI.
 */
final class Authorization
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
     * Determines whether or not user has access to requested resource.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface Status 401 if not authenticated, 403 if not authorized
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $route = $request->getAttribute('route', null);

        if ($route === null) {
            // User likely accessing a nonexistent route. Calling next middleware.
            return $next($request, $response);
        }

        $role = $this->getRole($this->auth->getIdentity());
        $resource = $route->getPattern();
        $privilege = $request->getMethod();
        $isAllowed = $this->acl->isAllowed($role, $resource, $privilege);
        $hasIdentity = $this->auth->hasIdentity();

        if ($hasIdentity && !$isAllowed) {
            // Authenticated but unauthorized for this resource
            return $response->withStatus(403);
        }

        if (!$hasIdentity && !$isAllowed) {
            // Not authenticated and must be authenticated to access this resource
            return $response->withStatus(401);
        }

        return $next($request, $response);
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
        if (is_object($identity)) {
            return $identity->getRole();
        }

        if (is_array($identity) && isset($identity['role'])) {
            return $identity['role'];
        }

        return 'guest';
    }
}
