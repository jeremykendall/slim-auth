<?php

namespace JeremyKendall\Slim\Auth\Exception;

use JeremyKendall\Slim\Auth\AuthException;

class HttpForbiddenException extends AuthException
{
    public function __construct(
        $message = 'You are not authorized to access this resource', 
        $code = 403, 
        \Exception $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
