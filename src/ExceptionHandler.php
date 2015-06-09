<?php
namespace Relay\Middleware;

use Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

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
            $response->getBody()->write($e->getMessage());
        }
        return $response;
    }
}
