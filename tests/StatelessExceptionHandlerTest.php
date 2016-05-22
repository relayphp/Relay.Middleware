<?php
namespace Relay\Middleware;

use Exception;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;

class StatelessExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function responseFactory()
    {
        return new Response();
    }

    public function testExceptional()
    {
        $exceptionHandler = new StatelessExceptionHandler([$this, 'responseFactory']);

        $originalResponse = new Response();
        $body = $originalResponse->getBody();
        $body->write('Original response');

        // Ensure that the handler can maintain behaviour over multiple cycles 
        for ($i = 0; $i < 5; $i++) {
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
    }

    public function testUnexceptional()
    {
        $exceptionHandler = new StatelessExceptionHandler([$this, 'responseFactory']);

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
