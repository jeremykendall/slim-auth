<?php

/**
 * Slim Auth
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 * @copyright Copyright (c) 2013 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth;

use JeremyKendall\Slim\Auth\Authenticator;
use JeremyKendall\Slim\Auth\Middleware\Authorization as AuthorizationMiddleware;
use Slim\Slim;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Permissions\Acl\Acl;

/**
 * Convenience class to assist wiring up Slim Auth defaults
 */
class Bootstrap
{
    /**
     * @var Acl Access control list
     */
    private $acl;

    /**
     * @var AbstractAdapter Auth adapter
     */
    private $adapter;

    /**
     * @var StorageInterface AuthenticationService storage
     */
    private $storage;

    /**
     * @var Slim Instance of Slim
     */
    private $app;

    /**
     * Public constructor
     *
     * @param Slim            $app
     * @param AbstractAdapter $adapter
     * @param Acl             $acl
     */
    public function __construct(Slim $app, AbstractAdapter $adapter, Acl $acl)
    {
        $this->app = $app;
        $this->adapter = $adapter;
        $this->acl = $acl;
    }

    /**
     * Wires up Slim Auth defaults
     *
     * Creates the Zend AuthenticationService, adds the AuthenticationService
     * and the Authenticator to the Slim resource locator, and adds the
     * AuthorizationMiddleware to the $app instance.
     */
    public function bootstrap()
    {
        $this->app->auth = function () {
            return new AuthenticationService(
                $this->getStorage(),
                $this->getAdapter()
            );
        };

        $app = $this->app;

        $this->app->authenticator = function () use ($app) {
            return new Authenticator($app->auth);
        };

        // Add the custom middleware
        $this->app->add(new AuthorizationMiddleware($this->app->auth, $this->getAcl()));
    }

    /**
     * Get acl
     *
     * @return acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Gets storage
     *
     * @return StorageInterface AuthenticationService storage
     */
    public function getStorage()
    {
        if ($this->storage === null) {
            $this->storage = new SessionStorage();
        }

        return $this->storage;
    }

    /**
     * Set storage
     *
     * @param StorageInterface $storage the value to set
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Gets auth adapter adapter
     *
     * @return AbstractAdapter Auth adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}
