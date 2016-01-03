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
 * HTTP 401 Exception.
 */
final class HttpUnauthorizedException extends \RuntimeException implements HttpException
{
    /**
     * @var int Http status code
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

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
