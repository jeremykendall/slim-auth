<?php

namespace JeremyKendall\Slim\Auth;

use JeremyKendall\Slim\Auth\Authenticator;
use JeremyKendall\Slim\Auth\Middleware\Authorization as AuthorizationMiddleware;
use Slim\Slim;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Permissions\Acl\Acl;

class ConfigHelper
{
    /**
     * @var CredentialStrategyInterface Credential strategy
     */
    private $credentialStrategy;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var AbstractAdapter
     */
    private $adapter;

    /**
     * @var StorageInterface AuthenticationService storage
     */
    private $storage;

    /**
     * @var Slim
     */
    private $app;

    /**
     * @var AuthenticationService
     */
    private $auth;

    public function __construct(Slim $app, AbstractAdapter $adapter, Acl $acl)
    {
        $this->app = $app;
        $this->adapter = $adapter;
        $this->acl = $acl;
    }

    public function create()
    {
        $this->auth = new AuthenticationService(
            $this->getStorage(),
            $this->getAdapter()
        );

        $auth = $this->auth;

        // Add the authenticator to the built-in resource locator
        $this->app->authenticator = function () use ($auth) {
            return new Authenticator($auth);
        };

        // Add the custom middleware
        $this->app->add(new AuthorizationMiddleware($auth, $this->getAcl()));
    }

    /**
     * Get credentialStrategy
     *
     * @return credentialStrategy
     */
    public function getCredentialStrategy()
    {
        return $this->credentialStrategy;
    }

    /**
     * Set credentialStrategy
     *
     * @param $credentialStrategy the value to set
     */
    public function setCredentialStrategy($credentialStrategy)
    {
        $this->credentialStrategy = $credentialStrategy;
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
     * Get adapter
     *
     * @return AbstractAdapter adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Get storage
     *
     * @return StorageInterface storage
     */
    public function getStorage()
    {
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
     * Get app
     *
     * @return Slim Instance of Slim
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Get auth
     *
     * @return AuthenticationSerivce auth
     */
    public function getAuth()
    {
        return $this->auth;
    }
}
