<?php
namespace Relay\Middleware;

use Exception;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;

class ExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptional()
    {
        $exceptionResponse = new Response();
        $exceptionHandler = new ExceptionHandler($exceptionResponse);

        $originalResponse = new Response();
        $body = $originalResponse->getBody();
        $body->write('Original response');

        $response = $exceptionHandler(
            ServerRequestFactory::fromGlobals(),
            $originalResponse,
            function ($request, $response) {
                throw new Exception('Random exception');
            }
        );

        $this->assertEquals(
            'Exception: Random exception',
            $response->getBody()->__toString()
        );
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testUnexceptional()
    {
        $exceptionResponse = new Response();
        $exceptionHandler = new ExceptionHandler($exceptionResponse);

        $originalResponse = new Response();
        $body = $originalResponse->getBody();
        $body->write('Original response');

        $response = $exceptionHandler(
            ServerRequestFactory::fromGlobals(),
            $originalResponse,
            function ($request, $response) {
                return $response;
            }
        );

        $this->assertEquals('Original response', $response->getBody()->__toString());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
