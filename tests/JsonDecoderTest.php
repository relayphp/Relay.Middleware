<?php
namespace Relay\Middleware;

use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Stream;


class JsonDecoderTest extends \PHPUnit_Framework_TestCase
{

    protected $data = ['foo', 'bar'];


    public function requestProvider()
    {
        return [
            ['GET'   , 'application/json', []],
            ['GET'   , null, []],
            ['POST'  , 'application/json', $this->data],
            ['POST'  , 'application/json; charset=utf-8', $this->data],
            ['POST'  , 'application/json ; charset=utf-8', $this->data],
            ['POST'  , null, []],
            ['PUT'   , 'application/json', $this->data],
            ['PUT'   , 'application/json; charset=utf-8', $this->data],
            ['PUT'   , 'application/json ; charset=utf-8', $this->data],
            ['PUT'   , null, []],
            ['PATCH' , 'application/json', $this->data],
            ['PATCH' , 'application/json; charset=utf-8', $this->data],
            ['PATCH' , 'application/json ; charset=utf-8', $this->data],
            ['PATCH' , null, []],
            ['other' , 'application/json', $this->data],
            ['other' , 'application/json; charset=utf-8', $this->data],
            ['other' , 'application/json ; charset=utf-8', $this->data],
            ['other' , null, []]
        ];
    }

    /**
     * @dataProvider requestProvider
     */
    public function testParser($method, $contentType, $expected)
    {
        $json = json_encode($this->data);

        $stream = new Stream('php://temp', 'wb+');
        $stream->write($json);

        $jsonDecoder = new JsonDecoder();

        $response = new Response();
        $request = ServerRequestFactory::fromGlobals()
            ->withMethod($method)
            ->withBody($stream);

        if ($contentType) {
            $request = $request->withHeader('Content-Type', $contentType);
        }

        $parsedRequest = $jsonDecoder(
            $request,
            $response,
            function ($request, $response) {
                return $request;
            }
        );

        $this->assertEquals($expected, $parsedRequest->getParsedBody());
    }
}
