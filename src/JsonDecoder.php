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

/**
 *
 * Deprecated handler for JSON content; consider using JsonContentHandler
 * instead.
 *
 * @package Relay.Middleware
 *
 */
class JsonDecoder
{
    /**
     * @var bool
     */
    protected $assoc;

    /**
     * @var int
     */
    protected $maxDepth;

    /**
     * @var int
     */
    protected $options;

    /**
     *
     * @param bool $assoc
     *
     * @param int $maxDepth
     *
     * @param int $options
     *
     */
    public function __construct($assoc = false, $maxDepth = 256, $options = 0)
    {
        $this->assoc = $assoc;
        $this->maxDepth = $maxDepth;
        $this->options = $options;
    }

    /**
     *
     * Parses the PSR-7 request body if its content-type is 'application/json'.
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
        $parts = explode(';', $request->getHeaderLine('Content-Type'));
        $type = strtolower(trim(array_shift($parts)));

        if ($request->getMethod() != 'GET' && $type == 'application/json') {
            $body = (string) $request->getBody();
            $request = $request->withParsedBody(json_decode(
                $body,
                $this->assoc,
                $this->maxDepth,
                $this->options
            ));
        }

        return $next($request, $response);
    }
}
