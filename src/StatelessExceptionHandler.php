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

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 *
 * Catches exceptions thrown by middleware later in the queue.
 *
 * This middleware differs from ExceptionHandler middleware as it is suitable
 * for use in applications where __invoke() may be called multiple times.
 *
 * @package relay/middleware
 *
 */
class StatelessExceptionHandler extends ExceptionHandler
{
    /**
     *
     * The factory to use for generating Responses to use when showing the
     * exception; this *replaces* the existing Response.
     *
     * @var callable
     *
     */
    protected $responseFactory;

    /**
     *
     * Constructor.
     *
     * @param callable $responseFactory The factory to use for creating Response
     * objects.
     *
     */
    public function __construct(callable $responseFactory)
    {
        $this->responseFactory = $responseFactory;

        parent::__construct($responseFactory());
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
        if (null === $this->exceptionResponse) {
            $this->exceptionResponse = call_user_func($this->responseFactory);  
        }

        $response = parent::__invoke($request, $response, $next);

        $this->exceptionResponse = null;

        return $response;
    }
}
