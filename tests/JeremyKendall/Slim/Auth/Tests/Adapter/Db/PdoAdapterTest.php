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
     * @var CredentialStrategyInterface
     */
    private $credentialStrategy;

    /**
     * @var PdoAdapter
     */
    private $adapter;

    /**
     * @var array User data
     */
    private $user;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var string identity/credential table name
     */
    private $tableName;

    /**
     * @var string credential column
     */
    private $credentialColumn;

    /**
     * @var string identity column
     */
    private $identityColumn;

    protected function setUp()
    {
        parent::setUp();
        $this->tableName = 'user_table';
        $this->credentialColumn = 'user_password';
        $this->identityColumn = 'user_unique_email';

        $this->user = array(
            $this->identityColumn => 'arthur.dent@example.com',
            'role' => 'hapless protagonist',
            $this->credentialColumn => 'passwordHash',
        );
        $this->setUpDb();
        $this->setUpAdapter();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testConstructorSetsDbAndFields()
    {
        $this->assertSame($this->db, $this->adapter->getDb());
        $this->assertSame($this->credentialStrategy, $this->adapter->getCredentialStrategy());
        $this->assertEquals($this->tableName, $this->adapter->getTableName());
        $this->assertEquals($this->identityColumn, $this->adapter->getIdentityColumn());
        $this->assertEquals($this->credentialColumn, $this->adapter->getCredentialColumn());
    }

    public function testAuthenticationSuccessWithDispatcher()
    {
        $this->adapter->setDispatcher($this->dispatcher);
        $this->user['id'] = '1';

        $this->credentialStrategy->expects($this->once())
            ->method('verifyPassword')
            ->with('00101010', 'passwordHash')
            ->will($this->returnValue(true));

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('user.password_validated', new PasswordValidatedEvent($this->user, $this->db))
            ->will($this->returnValue(true));

        $this->adapter->setIdentity('arthur.dent@example.com');
        $this->adapter->setCredential('00101010');

        $result = $this->adapter->authenticate();

        unset($this->user[$this->credentialColumn]);

        $this->assertTrue($result->isValid());
        $this->assertEquals($this->user, $result->getIdentity());
    }

    public function testAuthenticationSuccessWithoutDispatcher()
    {
        $this->user['id'] = '1';

        $this->credentialStrategy->expects($this->once())
            ->method('verifyPassword')
            ->with('00101010', 'passwordHash')
            ->will($this->returnValue(true));

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->adapter->setIdentity('arthur.dent@example.com');
        $this->adapter->setCredential('00101010');

        $result = $this->adapter->authenticate();

        unset($this->user[$this->credentialColumn]);

        $this->assertTrue($result->isValid());
        $this->assertEquals($this->user, $result->getIdentity());
    }

    public function testAuthenticationFailsBadPassword()
    {
        $this->credentialStrategy->expects($this->once())
            ->method('verifyPassword')
            ->with('bad password', 'passwordHash')
            ->will($this->returnValue(false));

        $this->adapter->setIdentity('arthur.dent@example.com');
        $this->adapter->setCredential('bad password');

        $result = $this->adapter->authenticate();
        $messages = $result->getMessages();

        $this->assertFalse($result->isValid());
        $this->assertEquals(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertEquals('Invalid username or password provided', $messages[0]);
    }

    public function testAuthenticationFailsUserNotFound()
    {
        $this->credentialStrategy->expects($this->never())
            ->method('verifyPassword');

        $this->adapter->setIdentity('zaphod.beeblebrox@example.com');
        $this->adapter->setCredential('dumb password');

        $result = $this->adapter->authenticate();
        $messages = $result->getMessages();

        $this->assertFalse($result->isValid());
        $this->assertEquals(Result::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
        $this->assertEquals('User not found.', $messages[0]);
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

        $create = 'CREATE TABLE IF NOT EXISTS [%s] ( '
            . '[id] INTEGER  NOT NULL PRIMARY KEY, '
            . '[%s] VARCHAR(50) NOT NULL, '
            . '[role] VARCHAR(50) NOT NULL, '
            . '[%s] VARCHAR(255) NULL)';
        $create = sprintf($create, $this->tableName, $this->identityColumn, $this->credentialColumn);

        $delete = sprintf('DELETE FROM %s', $this->tableName);

        $insert = 'INSERT INTO %s (%s, role, %s) '
            . 'VALUES (:%s, :role, :%s)';
        $insert = sprintf(
            $insert,
            $this->tableName,
            $this->identityColumn,
            $this->credentialColumn,
            $this->identityColumn,
            $this->credentialColumn
        );

        try {
            $this->db->exec($create);
            $this->db->exec($delete);
            //$this->db->exec($insert);
            $insert = $this->db->prepare($insert);
            $insert->execute($this->user);
        } catch (PDOException $e) {
            die(sprintf('DB setup error: %s', $e->getMessage()));
        }
    }

    private function setUpAdapter()
    {
        $interface = 'JeremyKendall\Slim\Auth\CredentialStrategy\CredentialStrategyInterface';

        $this->credentialStrategy = $this->getMockBuilder($interface)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter = new PdoAdapter(
            $this->db, 
            $this->credentialStrategy, 
            $this->tableName, 
            $this->identityColumn, 
            $this->credentialColumn
        );
    }
}
