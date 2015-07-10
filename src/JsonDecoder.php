<?php
namespace Relay\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class JsonDecoder
{
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $type   = $request->getHeader('Content-Type');
        $method = $request->getMethod();

        if ('GET' != $method
            && ! empty($type)
            && 'application/json' == strtolower($type[0])
        ) {
            $body    = (string) $request->getBody();
            $request = $request->withParsedBody(json_decode($body));
        }

        return $next($request, $response);
    }
}
