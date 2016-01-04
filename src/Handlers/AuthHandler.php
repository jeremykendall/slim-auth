<?php

/**
 * Slim Auth.
 *
 * @link      http://github.com/jeremykendall/slim-auth Canonical source repo
 *
 * @copyright Copyright (c) 2016 Jeremy Kendall (http://about.me/jeremykendall)
 * @license   http://github.com/jeremykendall/slim-auth/blob/master/LICENSE MIT
 */
namespace JeremyKendall\Slim\Auth\Handlers;

use Psr\Http\Message\ResponseInterface;

/**
 * Auth Handler interface.
 */
interface AuthHandler
{
    /**
     * Perform some action if user is not authenticated.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     *
     * @throws \JeremyKendall\Slim\Auth\Exception\HttpException
     */
    public function notAuthenticated(ResponseInterface $response);

    /**
     * Perform some action if user is not authorized.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     *
     * @throws \JeremyKendall\Slim\Auth\Exception\HttpException
     */
    public function notAuthorized(ResponseInterface $response);
}
