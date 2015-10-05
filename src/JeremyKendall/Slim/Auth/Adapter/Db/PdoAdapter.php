<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2015 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth\Adapter\Db;

use JeremyKendall\Password\PasswordValidatorInterface;
use PDO;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result as AuthenticationResult;

/**
 * Authentication adapter.
 */
class PdoAdapter extends AbstractAdapter
{
    /**
     * @var PDO DB connection
     */
    private $db;

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
     * @var PasswordValidatorInterface Handles password validation
     */
    protected $passwordValidator;

    /**
     * Public constructor.
     *
     * @param PDO                        $db
     * @param string                     $tableName
     * @param string                     $identityColumn
     * @param string                     $credentialColumn
     * @param PasswordValidatorInterface $passwordValidator Password validator
     */
    public function __construct(
        PDO $db,
        $tableName,
        $identityColumn,
        $credentialColumn,
        PasswordValidatorInterface $passwordValidator
    ) {
        $this->db = $db;
        $this->tableName = $tableName;
        $this->identityColumn = $identityColumn;
        $this->credentialColumn = $credentialColumn;
        $this->passwordValidator = $passwordValidator;
    }

    /**
     * Performs authentication.
     *
     * @return AuthenticationResult Authentication result
     */
    public function authenticate()
    {
        $user = $this->findUser();

        if ($user === false) {
            return new AuthenticationResult(
                AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND,
                array(),
                array('User not found.')
            );
        }

        $validationResult = $this->passwordValidator->isValid(
            $this->credential, $user[$this->credentialColumn], $user[$this->identityColumn]
        );

        if ($validationResult->isValid()) {
            // Don't store password in identity
            unset($user[$this->getCredentialColumn()]);

            return new AuthenticationResult(AuthenticationResult::SUCCESS, $user, array());
        }

        return new AuthenticationResult(
            AuthenticationResult::FAILURE_CREDENTIAL_INVALID,
            array(),
            array('Invalid username or password provided')
        );
    }

    /**
     * Finds user to authenticate.
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

        // Explicitly setting fetch mode fixes
        // https://github.com/jeremykendall/slim-auth/issues/13
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get tableName.
     *
     * @return string tableName
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get identityColumn.
     *
     * @return string identityColumn
     */
    public function getIdentityColumn()
    {
        return $this->identityColumn;
    }

    /**
     * Get credentialColumn.
     *
     * @return string credentialColumn
     */
    public function getCredentialColumn()
    {
        return $this->credentialColumn;
    }
}
