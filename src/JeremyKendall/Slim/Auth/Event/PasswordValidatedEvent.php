<?php

namespace JeremyKendall\Slim\Auth\Event;

use PDO;
use Symfony\Component\EventDispatcher\Event;

class PasswordValidatedEvent extends Event
{
    /**
     * @var array User data
     */
    private $user;

    /**
     * @var PDO Database connection
     */
    private $db;

    /**
     * Public constructor
     *
     * @param array $user User data
     * @param PDO   $db   Database connection
     */
    public function __construct(array $user, PDO $db)
    {
        $this->user = $user;
        $this->db = $db;
    }

    /**
     * Get user
     *
     * @return array User data
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get db
     *
     * @return PDO db connection
     */
    public function getDb()
    {
        return $this->db;
    }
}
