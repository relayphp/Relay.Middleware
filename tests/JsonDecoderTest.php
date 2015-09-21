<?php
namespace Relay\Middleware;

use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Stream;


class JsonDecoderTest extends \PHPUnit_Framework_TestCase
{

    protected $data = ['foo', 'bar'];
    protected $dataAssoc = ['foo'=>'baz', 'bar'=>'qux'];


    public function requestProvider()
    {
        return [
            ['GET'   , 'application/json', [],[]],
            ['GET'   , null, [],[]],
            ['POST'  , 'application/json', $this->data, $this->dataAssoc],
            ['POST'  , 'application/json; charset=utf-8', $this->data, $this->dataAssoc],
            ['POST'  , 'application/json ; charset=utf-8',$this->data, $this->dataAssoc],
            ['POST'  , null, [], []],
            ['PUT'   , 'application/json', $this->data, $this->dataAssoc],
            ['PUT'   , 'application/json; charset=utf-8', $this->data, $this->dataAssoc],
            ['PUT'   , 'application/json ; charset=utf-8', $this->data, $this->dataAssoc],
            ['PUT'   , null, [], []],
            ['PATCH' , 'application/json', $this->data, $this->dataAssoc],
            ['PATCH' , 'application/json; charset=utf-8', $this->data, $this->dataAssoc],
            ['PATCH' , 'application/json ; charset=utf-8', $this->data, $this->dataAssoc],
            ['PATCH' , null, [], []],
            ['other' , 'application/json', $this->data, $this->dataAssoc],
            ['other' , 'application/json; charset=utf-8', $this->data, $this->dataAssoc],
            ['other' , 'application/json ; charset=utf-8', $this->data, $this->dataAssoc],
            ['other' , null, [], []]
        ];
    }

    /**
     * @dataProvider requestProvider
     */
    public function testParser($method, $contentType, $expected, $alt)
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
    
    /**
     * @dataProvider requestProvider
     */
    public function testDecodeToAssoc($method, $contentType, $alt, $expected)
    {
        $json = json_encode($this->dataAssoc);

        $stream = new Stream('php://temp', 'wb+');
        $stream->write($json);

        $jsonDecoder = new JsonDecoder(true);

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
    
    /**
     * @dataProvider requestProvider
     */
    public function testDecodeToObject($method, $contentType, $alt, $expected)
    {
        $expected = $expected?(object) $expected:[];
        $json = json_encode($this->dataAssoc);

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
