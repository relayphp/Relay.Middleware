<?php
/**
 *
 * This file is part of Relay for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @copyright 2015-2016, Relay for PHP
 *
 */
namespace Relay\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

/**
 *
 * Base class for content handlers.
 *
 * @package Relay.Middleware
 *
 */
abstract class ContentHandler
{
    /**
     * @var array Methods that cannot have request bodies
     */
    protected $httpMethodsWithoutContent = [
        'GET',
        'HEAD',
    ];

    /**
     * Check if the content type is appropriate for handling
     *
     * @param string $mime
     *
     * @return boolean
     */
    abstract protected function isApplicableMimeType($mime);

    /**
     * Parse the request body
     *
     * @uses throwException()
     *
     * @param string $body
     *
     * @return mixed
     */
    abstract protected function getParsedBody($body);

    /**
     * Throw an exception when parsing fails
     *
     * @param string $message
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function throwException($message)
    {
        throw new RuntimeException($message);
    }

    /**
     * Parses request bodies based on content type
     *
     * @param  Request  $request
     * @param  Response $response
     * @param  callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        if (!in_array($request->getMethod(), $this->httpMethodsWithoutContent)) {
            $parts = explode(';', $request->getHeaderLine('Content-Type'));
            $mime  = strtolower(trim(array_shift($parts)));

            if ($this->isApplicableMimeType($mime) && !$request->getParsedBody()) {
                $parsed  = $this->getParsedBody((string) $request->getBody());
                $request = $request->withParsedBody($parsed);
            }
        }

        return $next($request, $response);
    }
}
