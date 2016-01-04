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
 * Redirects user to appropriate location based on authentication/authorization.
 */
final class RedirectHandler implements AuthHandler
{
    private $redirectNotAuthenticated;
    private $redirectNotAuthorized;

    public function __construct($redirectNotAuthenticated, $redirectNotAuthorized)
    {
        $this->redirectNotAuthenticated = $redirectNotAuthenticated;
        $this->redirectNotAuthorized = $redirectNotAuthorized;
    }

    /**
     * {@inheritDoc}
     */
    public function notAuthenticated(ResponseInterface $response)
    {
        return $response->withStatus(302)
            ->withHeader('Location', $this->redirectNotAuthenticated);
    }

    /**
     * {@inheritDoc}
     */
    public function notAuthorized(ResponseInterface $response)
    {
        return $response->withStatus(302)
            ->withHeader('Location', $this->redirectNotAuthorized);
    }
}
