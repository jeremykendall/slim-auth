<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2013-2016 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */
namespace JeremyKendall\Slim\Auth\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Provides Slim Auth services for use with a \Slim\Container.
 *
 * You MUST provide your own AuthAdapter and Acl in your instance of \Slim\Container.
 *
 * @see http://pimple.sensiolabs.org/#extending-a-container Pimple - Extending a Container
 */
final class SlimAuthProvider implements ServiceProviderInterface
{
    /**
     * Registers Slim Auth services on the given container.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $pimple['auth'] = function ($c) {
            $auth = new \Zend\Authentication\AuthenticationService();
            $auth->setAdapter($c->get('authAdapter'));

            if ($c->has('authStorage')) {
                $auth->setStorage($c->get('authStorage'));
            }

            return $auth;
        };

        $pimple['redirectHandler'] = function ($c) {
            $redirectNotAuthenticated = '/login';
            $redirectNotAuthorized = '/403';

            if (isset($c['redirectNotAuthenticated'])) {
                $redirectNotAuthenticated = $c['redirectNotAuthenticated'];
            }

            if (isset($c['redirectNotAuthorized'])) {
                $redirectNotAuthorized = $c['redirectNotAuthorized'];
            }

            return new \JeremyKendall\Slim\Auth\Handlers\RedirectHandler(
                $redirectNotAuthenticated,
                $redirectNotAuthorized
            );
        };

        $pimple['throwHttpExceptionHandler'] = function ($c) {
            return new \JeremyKendall\Slim\Auth\Handlers\ThrowHttpExceptionHandler();
        };

        $pimple['slimAuthRedirectMiddleware'] = function ($c) {
            return new \JeremyKendall\Slim\Auth\Middleware\Authorization(
                $c->get('auth'),
                $c->get('acl'),
                $c->get('redirectHandler')
            );
        };

        $pimple['slimAuthThrowHttpExceptionMiddleware'] = function ($c) {
            return new \JeremyKendall\Slim\Auth\Middleware\Authorization(
                $c->get('auth'),
                $c->get('acl'),
                $c->get('throwHttpExceptionHandler')
            );
        };

        $pimple['authenticator'] = function ($c) {
            return new \JeremyKendall\Slim\Auth\Authenticator($c->get('auth'));
        };
    }
}
