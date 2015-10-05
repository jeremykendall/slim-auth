<?php

namespace JeremyKendall\Slim\Auth\Tests\Adapter\Db;

use JeremyKendall\Password\PasswordValidatorInterface;
use JeremyKendall\Password\Result as ValidationResult;
use JeremyKendall\Slim\Auth\Adapter\Db\PdoAdapter;
use PDO;
use Zend\Authentication\Result;

class PdoAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PDO
     */
    private $db;

    /**
     * @var PdoAdapter
     */
    private $adapter;

    /**
     * @var array User data
     */
    private $identity;

    /**
     * @var string User plain text password
     */
    private $plainTextPassword;

    /**
     * @var PasswordValidatorInterface
     */
    protected $passwordValidator;

    protected function setUp()
    {
        parent::setUp();

        $this->plainTextPassword = '00101010';

        $this->identity = array(
            'id' => 1,
            'email_address' => 'arthur.dent@example.com',
            'role' => 'hapless protagonist',
            'hashed_password' => password_hash('00101010', PASSWORD_DEFAULT),
        );

        $this->setUpDb();
        $this->setUpAdapter();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->db = null;
    }

    public function testAuthenticationSuccess()
    {
        $this->passwordValidator->expects($this->once())
            ->method('isValid')
            ->with(
                $this->plainTextPassword,
                $this->identity['hashed_password'],
                $this->identity['email_address']
            )
            ->will($this->returnValue(new ValidationResult(ValidationResult::SUCCESS)));

        $this->adapter->setIdentity($this->identity['email_address']);
        $this->adapter->setCredential($this->plainTextPassword);

        $result = $this->adapter->authenticate();
        $this->assertTrue($result->isValid());

        // Password must not be stored in identity
        unset($this->identity['hashed_password']);
        $this->assertEquals($this->identity, $result->getIdentity());
    }

    public function testAuthenticationFailsBadPassword()
    {
        $this->adapter->setIdentity($this->identity['email_address']);
        $this->adapter->setCredential('bad password');

        $this->passwordValidator->expects($this->once())
            ->method('isValid')
            ->with(
                'bad password',
                $this->identity['hashed_password'],
                $this->identity['email_address']
            )
            ->will($this->returnValue(
                new ValidationResult(ValidationResult::FAILURE_PASSWORD_INVALID)
            ));

        $result = $this->adapter->authenticate();
        $messages = $result->getMessages();

        $this->assertFalse($result->isValid());
        $this->assertEquals(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertEquals('Invalid username or password provided', $messages[0]);
    }

    public function testAuthenticationFailsUserNotFound()
    {
        $this->adapter->setIdentity('zaphod.beeblebrox@example.com');
        $this->adapter->setCredential('dumb password');

        $result = $this->adapter->authenticate();
        $messages = $result->getMessages();

        $this->assertFalse($result->isValid());
        $this->assertEquals(Result::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
        $this->assertEquals('User not found.', $messages[0]);
    }

    /**
     * @link https://github.com/jeremykendall/slim-auth/issues/13
     */
    public function testIssue13()
    {
        $this->setUpDb(PDO::FETCH_OBJ);
        $this->setUpAdapter();

        $this->passwordValidator->expects($this->once())
            ->method('isValid')
            ->with(
                $this->plainTextPassword,
                $this->identity['hashed_password'],
                $this->identity['email_address']
            )
            ->will($this->returnValue(new ValidationResult(ValidationResult::SUCCESS)));

        $this->adapter->setIdentity($this->identity['email_address']);
        $this->adapter->setCredential($this->plainTextPassword);

        $result = $this->adapter->authenticate();
        $this->assertTrue($result->isValid());
    }

    private function setUpDb($fetchStyle = PDO::FETCH_ASSOC)
    {
        $dsn = 'sqlite::memory:';
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => $fetchStyle,
        );

        try {
            $this->db = new PDO($dsn, null, null, $options);
        } catch (PDOException $e) {
            die(sprintf('DB connection error: %s', $e->getMessage()));
        }

        $create = 'CREATE TABLE IF NOT EXISTS [application_users] ( '
            . '[id] INTEGER  NOT NULL PRIMARY KEY, '
            . '[email_address] VARCHAR(50) NOT NULL, '
            . '[role] VARCHAR(50) NOT NULL, '
            . '[hashed_password] VARCHAR(255) NULL)';

        $delete = 'DELETE FROM application_users';

        $insert = 'INSERT INTO application_users (id, email_address, role, hashed_password) '
            . 'VALUES (:id, :email_address, :role, :hashed_password)';

        try {
            $this->db->exec($create);
            $this->db->exec($delete);
            $insert = $this->db->prepare($insert);
            $insert->execute($this->identity);
        } catch (PDOException $e) {
            die(sprintf('DB setup error: %s', $e->getMessage()));
        }
    }

    private function setUpAdapter()
    {
        $this->passwordValidator =
            $this->getMock('JeremyKendall\Password\PasswordValidatorInterface');

        $this->adapter = new PdoAdapter(
            $this->db,
            $tableName = 'application_users',
            $identityColumn = 'email_address',
            $credentialColumn = 'hashed_password',
            $this->passwordValidator
        );
    }
}
