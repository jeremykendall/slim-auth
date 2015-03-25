<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2015 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */

namespace JeremyKendall\Slim\Auth\Exception;

use JeremyKendall\Slim\Auth\AuthException;

/**
 * HTTP 403 Exception.
 */
class HttpForbiddenException extends AuthException
{
    /**
     * Public constructor.
     *
     * @param string    $message  Exception message
     * @param int       $code     Exception code
     * @param Exception $previous Previous exception
     */
    public function __construct(
        $message = 'You are not authorized to access this resource',
        $code = 403,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
