<?php
namespace Relay\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class JsonDecoder
{
    private $assoc;
    
    public function __construct($assoc = 0){
        $this->assoc = $assoc;
    }
    
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $parts  = explode(';', $request->getHeaderLine('Content-Type'));
        $type   = trim(array_shift($parts));
        $method = $request->getMethod();

        if ('GET' != $method
            && ! empty($type)
            && 'application/json' == strtolower($type)
        ) {
            $body    = (string) $request->getBody();
            $request = $request->withParsedBody(json_decode($body, $this->assoc));
        }

        return $next($request, $response);
    }
}
