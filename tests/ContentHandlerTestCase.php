<?php
namespace Relay\Middleware;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

abstract class ContentHandlerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $method
     * @param string $mime
     * @param string $body
     *
     * @return ServerRequest
     */
    protected function getRequest($method, $mime, $body = null)
    {
        $stream = new Stream('php://memory', 'w+');
        if ($body) {
            $stream->write($body);
        }
        return new ServerRequest(
            $server  = [],
            $upload  = [],
            $path    = '/',
            $method,
            $body    = $stream,
            $headers = [
                'Content-Type' => $mime,
            ]
        );
    }
}
