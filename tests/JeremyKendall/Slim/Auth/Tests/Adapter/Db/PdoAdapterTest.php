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

    protected function setUp()
    {
        parent::setUp();
        $this->user = array(
            'email' => 'Arthur.Dent@Example.Com',
            'emailCanonical' => 'arthur.dent@example.com',
            'role' => 'hapless protagonist',
            'password' => 'passwordHash',
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
        $this->assertEquals('users', $this->adapter->getTableName());
        $this->assertEquals('emailCanonical', $this->adapter->getIdentityColumn());
        $this->assertEquals('password', $this->adapter->getCredentialColumn());
    }

    public function testAuthenticationSuccess()
    {
        $this->credentialStrategy->expects($this->once())
            ->method('verifyPassword')
            ->with('00101010', 'passwordHash')
            ->will($this->returnValue(true));

        $this->user['id'] = '1';

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('user.password_validated', new PasswordValidatedEvent($this->user, $this->db))
            ->will($this->returnValue(true));

        $this->adapter->setIdentity('arthur.dent@example.com');
        $this->adapter->setCredential('00101010');

        $result = $this->adapter->authenticate();

        unset($this->user['password']);

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

        $this->assertFalse($result->isValid());
        $this->assertEquals(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertEquals('Invalid username or password provided', $result->getMessages()[0]);
    }

    public function testAuthenticationFailsUserNotFound()
    {
        $this->credentialStrategy->expects($this->never())
            ->method('verifyPassword');

        $this->adapter->setIdentity('zaphod.beeblebrox@example.com');
        $this->adapter->setCredential('dumb password');

        $result = $this->adapter->authenticate();

        $this->assertFalse($result->isValid());
        $this->assertEquals(Result::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
        $this->assertEquals('User not found.', $result->getMessages()[0]);
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

        $create = 'CREATE TABLE IF NOT EXISTS [users] ( '
            . '[id] INTEGER  NOT NULL PRIMARY KEY, '
            . '[email] VARCHAR(50) NOT NULL, '
            . '[emailCanonical] VARCHAR(50) NOT NULL, '
            . '[role] VARCHAR(50) NOT NULL, '
            . '[password] VARCHAR(255) NULL)';

        $delete = 'DELETE FROM users';

        $insert = 'INSERT INTO users (email, emailCanonical, role, password) '
            . 'VALUES (:email, :emailCanonical, :role, :password)';

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
            $this->dispatcher,
            'users', 
            'emailCanonical', 
            'password'
        );
    }
}
