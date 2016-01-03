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

/**
 * HTTP 403 Exception.
 */
final class HttpForbiddenException extends \RuntimeException implements HttpException
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
        $message = 'You are not authorized to access this resource';
        $code = 403;
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
