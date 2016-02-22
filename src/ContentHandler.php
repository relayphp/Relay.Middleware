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
     *
     * Methods that cannot have request bodies.
     *
     * @var array
     *
     */
    protected $httpMethodsWithoutContent = [
        'GET',
        'HEAD',
    ];

    /**
     *
     * Checks if the content type is appropriate for handling.
     *
     * @param string $mime The mime type.
     *
     * @return boolean
     *
     */
    abstract protected function isApplicableMimeType($mime);

    /**
     *
     * Parses the request body.
     *
     * @param string $body The request body.
     *
     * @return mixed
     *
     * @uses throwException()
     *
     */
    abstract protected function getParsedBody($body);

    /**
     *
     * Throws a a RuntimeException.
     *
     * @param string $message The message for the exception.
     *
     * @return null
     *
     * @throws RuntimeException
     *
     */
    protected function throwException($message)
    {
        throw new RuntimeException($message);
    }

    /**
     *
     * Parses the PSR-7 request body based on content type.
     *
     * @param Request $request The HTTP request.
     *
     * @param Response $response The HTTP response.
     *
     * @param callable $next The next middleware in the queue.
     *
     * @return Response
     *
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $isContentMethod = ! in_array(
            $request->getMethod(),
            $this->httpMethodsWithoutContent
        );

        if ($isContentMethod) {
            $request = $this->requestWithParsedBody($request);
        }

        return $next($request, $response);
    }

    /**
     *
     * Returns a Request with parsed body content.
     *
     * @param Request $request The HTTP request.
     *
     * @return Request
     *
     */
    protected function requestWithParsedBody(Request $request)
    {
        $parts = explode(';', $request->getHeaderLine('Content-Type'));
        $mime = strtolower(trim(array_shift($parts)));
        if ($this->isApplicableMimeType($mime) && ! $request->getParsedBody()) {
            $parsed = $this->getParsedBody((string) $request->getBody());
            $request = $request->withParsedBody($parsed);
        }
        return $request;
    }
}
