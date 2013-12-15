<?php

/**
 * Slim Auth
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 * @copyright Copyright (c) 2013 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth\Event;

use PDO;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched after password is validated
 *
 * Intended to be used to facilitate password rehashing. The User array should
 * contain the user's current password hash and enough identifying info that
 * the PDO connection can be used to update the password hash in the database.
 */
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
