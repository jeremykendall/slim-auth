<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2013-2016 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */
namespace JeremyKendall\Slim\Auth\Exception;

/**
 * Slim Auth HTTP Exception Interface.
 */
interface HttpException
{
    /**
     * Get HTTP status code.
     *
     * @return int HTTP status code
     */
    public function getStatusCode();
}
