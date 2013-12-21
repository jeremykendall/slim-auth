<?php

/**
 * Slim Auth
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 * @copyright Copyright (c) 2013 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth\Adapter\Db;

use JeremyKendall\Slim\Auth\Event\PasswordValidatedEvent;
use PDO;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result;

/**
 * Authentication adapter
 */
class PdoAdapter extends AbstractAdapter
{
    /**
     * @var PDO DB connection
     */
    private $db;

    /**
     * @var EventDispatcher Symfony event dispatcher
     */
    private $dispatcher;

    /**
     * @var string the table name to check
     */
    private $tableName;

    /**
     * @var string the column to use as the identity
     */
    private $identityColumn;

    /**
     * @var string column to be used as the credential
     */
    private $credentialColumn;

    /**
     * @var callable Handles credential validation
     */
    protected $credentialValidationCallback;

    /**
     * Public constructor
     *
     * @param PDO      $db
     * @param string   $tableName
     * @param string   $identityColumn
     * @param string   $credentialColumn
     * @param callable $credentialValidationCallback Optional credential handling
     */
    public function __construct(
        PDO $db,
        $tableName,
        $identityColumn,
        $credentialColumn,
        $credentialValidationCallback = null
    )
    {
        $this->db = $db;
        $this->tableName = $tableName;
        $this->identityColumn = $identityColumn;
        $this->credentialColumn = $credentialColumn;

        if (null === $credentialValidationCallback) {
            $this->setCredentialValidationCallback(function($password, $hash) {
                return password_verify($password, $hash);
            });
        } else {
            $this->setCredentialValidationCallback($credentialValidationCallback);
        }
    }

    /**
     * Performs authentication
     *
     * Includes a Symfony EventDispatcher Event, 'user.password_validated',
     * intended to be used for password rehashing, if it's needed.
     * @see http://symfony.com/doc/current/components/event_dispatcher/introduction.html
     *
     * @return Result Authentication result
     */
    public function authenticate()
    {
        $user = $this->findUser();

        if ($user === false) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                array(),
                array('User not found.')
            );
        }

        $passwordValid = call_user_func(
            $this->credentialValidationCallback,
            $this->credential, $user[$this->credentialColumn]
        );

        if ($passwordValid) {
            if ($this->getDispatcher()) {
                $event = new PasswordValidatedEvent($user, $this->db);
                $this->dispatcher->dispatch('user.password_validated', $event);
            }

            // Don't store password in identity
            unset($user[$this->getCredentialColumn()]);

            return new Result(Result::SUCCESS, $user, array());
        } else {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                array(),
                array('Invalid username or password provided')
            );
        }
    }

    /**
     * Finds user to authenticate
     *
     * @return array|null Array of user data, null if no user found
     */
    private function findUser()
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :identity',
            $this->getTableName(),
            $this->getIdentityColumn()
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array('identity' => $this->getIdentity()));

        return $stmt->fetch();
    }

    /**
     * Get tableName
     *
     * @return string tableName
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get identityColumn
     *
     * @return string identityColumn
     */
    public function getIdentityColumn()
    {
        return $this->identityColumn;
    }

    /**
     * Get credentialColumn
     *
     * @return string credentialColumn
     */
    public function getCredentialColumn()
    {
        return $this->credentialColumn;
    }

    /**
     * Sets callback used for credential validation
     *
     * @param  callable                  $validationCallback
     * @throws \InvalidArgumentException
     */
    public function setCredentialValidationCallback($validationCallback)
    {
        if (!is_callable($validationCallback)) {
            throw new \InvalidArgumentException('Invalid callback provided');
        }

        $this->credentialValidationCallback = $validationCallback;
    }

    /**
     * Get dispatcher
     *
     * @return EventDispatcher dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Set dispatcher
     *
     * @param EventDispatcher $dispatcher the value to set
     */
    public function setDispatcher(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
}
