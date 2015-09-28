<?php
namespace Relay\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class JsonDecoder
{
    protected $assoc;
    protected $maxDepth;
    protected $options;
    
    public function __construct($assoc = false, $max_depth = 256, $options = 0){
        $this->assoc = $assoc;
        $this->max_depth = $maxDepth;
        $this->options = $options;
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
            $request = $request->withParsedBody(json_decode($body,
                                                            $this->assoc,
                                                            $this->maxDepth,
                                                            $this->options));
        }

        return $next($request, $response);
    }
}
