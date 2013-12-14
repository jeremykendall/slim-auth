<?php

namespace JeremyKendall\Slim\Auth;

interface IdentityInterface
{
    /**
     * Gets user's role
     *
     * @return string User's role in application
     */
    public function getRole();
}
