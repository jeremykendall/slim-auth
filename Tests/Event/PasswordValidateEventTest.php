<?php

namespace JeremyKendall\Slim\Auth\Tests\Event;

use JeremyKendall\Slim\Auth\Event\PasswordValidatedEvent;
use PDO;

class PasswordValidatedEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $user = array('user' => 'Ford Prefect');
        $db = new PDO('sqlite::memory:');

        $event = new PasswordValidatedEvent($user, $db);

        $this->assertEquals($user, $event->getUser());
        $this->assertSame($db, $event->getDb());
    }
}
