<?php

namespace JeremyKendall\Slim\Auth\Tests\Adapter\Db;

use JeremyKendall\Slim\Auth\Adapter\Db\PdoAdapter;
use JeremyKendall\Slim\Auth\Event\PasswordValidatedEvent;
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
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var array User data
     */
    private $identity;

    protected function setUp()
    {
        parent::setUp();

        $this->identity = array(
            'email_address' => 'arthur.dent@example.com',
            'role' => 'hapless protagonist',
            'hashed_password' => '00101010',
        );

        $this->setUpDb();
        $this->setUpAdapter();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->db = null;
    }

    public function testAuthenticationSuccessWithoutDispatcher()
    {
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->adapter->setIdentity($this->identity['email_address']);
        $this->adapter->setCredential($this->identity['hashed_password']);

        $result = $this->adapter->authenticate();
        $this->assertTrue($result->isValid());

        $this->identity['id'] = '1';
        // Password must not be stored in identity
        unset($this->identity['hashed_password']);
        $this->assertEquals($this->identity, $result->getIdentity());
    }

    public function testAuthenticationSuccessWithDispatcher()
    {
        $this->adapter->setDispatcher($this->dispatcher);

        $this->identity['id'] = '1';

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                'user.password_validated', 
                new PasswordValidatedEvent($this->identity, $this->db)
            )
            ->will($this->returnValue(true));

        $this->adapter->setIdentity($this->identity['email_address']);
        $this->adapter->setCredential($this->identity['hashed_password']);

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

    public function testDefaultValidationCallback()
    {
        $hash = password_hash($this->identity['hashed_password'], PASSWORD_DEFAULT);
        $update = 'UPDATE application_users SET hashed_password = :pass WHERE id = 1';
        $stmt = $this->db->prepare($update);
        $stmt->execute(array(':pass' => $hash));

        $adapter = new PdoAdapter(
            $this->db, 
            $tableName = 'application_users',
            $identityColumn = 'email_address',
            $credentialColumn = 'hashed_password'
        );

        $adapter->setIdentity($this->identity['email_address']);
        $adapter->setCredential($this->identity['hashed_password']);

        $result = $adapter->authenticate();
        $this->assertTrue($result->isValid());

        $adapter->setCredential('bad pass');
        $result = $adapter->authenticate();
        $this->assertFalse($result->isValid());
    }

    public function testSetCallbackNotCallableThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid callback provided');
        $this->adapter->setCredentialValidationCallback('not_callable');
    }

    private function setUpDb()
    {
        $dsn = 'sqlite::memory:';
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
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

        $insert = 'INSERT INTO application_users (email_address, role, hashed_password) '
            . 'VALUES (:email_address, :role, :hashed_password)';

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
        $this->dispatcher = $this->getMockBuilder(
            'Symfony\Component\EventDispatcher\EventDispatcher'
        )
        ->disableOriginalConstructor()
        ->getMock();

        $this->credentialCallback = function ($a, $b) {
            return $a === $b;
        };

        $this->adapter = new PdoAdapter(
            $this->db, 
            $tableName = 'application_users',
            $identityColumn = 'email_address',
            $credentialColumn = 'hashed_password',
            $this->credentialCallback
        );
    }
}
