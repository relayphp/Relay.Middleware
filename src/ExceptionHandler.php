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

use Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 *
 * Catches exceptions thrown by middleware later in the queue.
 *
 * @package Relay.Middleware
 *
 */
class ExceptionHandler
{
    protected $exceptionResponse;

    public function __construct(Response $exceptionResponse)
    {
        $this->exceptionResponse = $exceptionResponse;
    }

    public function __invoke(Request $request, Response $response, callable $next)
    {
        try {
            $response = $next($request, $response);
        } catch (Exception $e) {
            $response = $this->exceptionResponse->withStatus(500);
            $response->getBody()->write(get_class($e) . ': ' . $e->getMessage());
        }
        return $response;
    }
}
