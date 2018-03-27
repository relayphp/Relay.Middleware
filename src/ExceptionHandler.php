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
 * @package relay/middleware
 *
 */
class ExceptionHandler
{
    /**
     *
     * The Response to use when showing the exception; this *replaces* the
     * existing Response.
     *
     * @var Response
     *
     */
    protected $exceptionResponse;

    /**
     *
     * Constructor.
     *
     * @param Response $exceptionResponse The Response to use when showing the
     * exception.
     *
     */
    public function __construct(Response $exceptionResponse)
    {
        $this->exceptionResponse = $exceptionResponse;
    }

    /**
     *
     * Catches any exception thrown in the queue this middleware, and puts its
     * message into the Response.
     *
     * @param Request $request The request.
     *
     * @param Response $response The response.
     *
     * @param callable $next The next middleware in the queue.
     *
     * @return Response
     *
     */
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
