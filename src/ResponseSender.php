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
 * Sends the PSR-7 response.
 *
 * @package Relay.Middleware
 *
 */
class ResponseSender
{
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $response = $next($request, $response);
        $this->sendStatus($response);
        $this->sendHeaders($response);
        $this->sendBody($response);
        return $response;
    }

    protected function sendStatus(Response $response)
    {
        $version = $response->getProtocolVersion();
        $status = $response->getStatusCode();
        $phrase = $response->getReasonPhrase();
        header("HTTP/{$version} {$status} {$phrase}");
    }

    protected function sendHeaders(Response $response)
    {
        foreach ($response->getHeaders() as $name => $values) {
            $this->sendHeader($name, $values);
        }
    }

    protected function sendHeader($name, $values)
    {
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '-', $name);
        foreach ($values as $value) {
            header("{$name}: {$value}", false);
        }
    }

    protected function sendBody(Response $response)
    {
        $stream = $response->getBody();
        $stream->rewind();
        while (! $stream->eof()) {
            echo $stream->read(8192);
        }
    }
}
