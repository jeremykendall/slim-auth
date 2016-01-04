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
 * HTTP 401 Unauthorized Exception.
 *
 * I think the name of this class, and of the HTTP response code, is confusing. 
 * It's intended to be used when a user attempts to access a resource when they 
 * are not authenticated and the resource requires authentication. That's 
 * AUTHENTICATION, not AUTHORIZATION, so confusing, but there ya go.
 *
 * @see https://httpstatuses.com/401
 */
final class HttpUnauthorizedException extends AuthException implements HttpException
{
    /**
     * @var int HTTP status code
     */
    private $statusCode;

    /**
     * Public constructor.
     */
    public function __construct()
    {
        $message = 'You must authenticate to access this resource.';
        $code = 401;
        $this->statusCode = $code;

        parent::__construct($message, $code);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
