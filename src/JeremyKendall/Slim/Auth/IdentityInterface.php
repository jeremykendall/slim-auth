<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2015 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth;

/**
 * Interface for identity classes.
 */
interface IdentityInterface
{
    /**
     * Gets user's role.
     *
     * @return string User's role in application
     */
    public function getRole();
}
