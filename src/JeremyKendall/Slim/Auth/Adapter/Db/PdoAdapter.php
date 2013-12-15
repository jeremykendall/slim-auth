<?php

namespace JeremyKendall\Slim\Auth\Adapter\Db;

use JeremyKendall\Slim\Auth\CredentialStrategy\CredentialStrategyInterface as CredentialStrategy;
use JeremyKendall\Slim\Auth\Event\PasswordValidatedEvent;
use PDO;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result;

class PdoAdapter extends AbstractAdapter
{
    /**
     * @var PDO
     */
    private $db;

    /**
     * @var CredentialStrategy
     */
    private $credentialStrategy;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * $tableName - the table name to check
     *
     * @var string
     */
    private $tableName;

    /**
     * $identityColumn - the column to use as the identity
     *
     * @var string
     */
    private $identityColumn;

    /**
     * $credentialColumn - column to be used as the credential
     *
     * @var string
     */
    private $credentialColumn;

    /**
     * Public constructor
     *
     * @param PDO                $db
     * @param CredentialStrategy $credentialStrategy
     * @param EventDispatcher    $dispatcher
     * @param string             $tableName
     * @param string             $identityColumn
     * @param string             $credentialColumn
     */
    public function __construct(
        PDO $db,
        CredentialStrategy $credentialStrategy,
        EventDispatcher $dispatcher,
        $tableName,
        $identityColumn,
        $credentialColumn
    )
    {
        $this->db = $db;
        $this->credentialStrategy = $credentialStrategy;
        $this->dispatcher = $dispatcher;
        $this->tableName = $tableName;
        $this->identityColumn = $identityColumn;
        $this->credentialColumn = $credentialColumn;
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

        $passwordValid = $this->credentialStrategy
            ->verifyPassword($this->getCredential(), $user['password']);

        if ($passwordValid) {
            $event = new PasswordValidatedEvent($user, $this->db);
            $this->dispatcher->dispatch('user.password_validated', $event);

            // Don't store password in identity
            unset($user['password']);

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
     * Get db
     *
     * @return PDO Database connection
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Get tableName
     *
     * @return tableName
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get identityColumn
     *
     * @return identityColumn
     */
    public function getIdentityColumn()
    {
        return $this->identityColumn;
    }

    /**
     * Get credentialColumn
     *
     * @return credentialColumn
     */
    public function getCredentialColumn()
    {
        return $this->credentialColumn;
    }

    /**
     * Get credentialStrategy
     *
     * @return CredentialStrategy credentialStrategy
     */
    public function getCredentialStrategy()
    {
        return $this->credentialStrategy;
    }
}
