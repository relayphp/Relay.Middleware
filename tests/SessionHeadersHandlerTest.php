<?php
namespace Relay\Middleware;

use RuntimeException;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Stream;

class SessionHeadersHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $stream;

    protected function setUp()
    {
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cache_limiter', '');
    }

    protected function newHandler($cacheLimiter = 'nocache')
    {
        return new SessionHeadersHandler($cacheLimiter);
    }

    public function testIni_useTransSid()
    {
        ini_set('session.use_trans_sid', 1);
        $this->setExpectedException(
            RuntimeException::CLASS,
            "The .ini setting 'session.use_trans_sid' must be false."
        );
        $handler = $this->newHandler();
    }

    public function testIni_useCookies()
    {
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_cookies', 1);
        $this->setExpectedException(
            RuntimeException::CLASS,
            "The .ini setting 'session.use_cookies' must be false."
        );
        $handler = $this->newHandler();
    }

    public function testIni_useOnlyCookies()
    {
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 0);
        $this->setExpectedException(
            RuntimeException::CLASS,
            "The .ini setting 'session.use_only_cookies' must be true."
        );
        $handler = $this->newHandler();
    }

    public function testIni_cacheLimiter()
    {
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cache_limiter', 'nocache');
        $this->setExpectedException(
            RuntimeException::CLASS,
            "The .ini setting 'session.cache_limiter' must be an empty string."
        );
        $handler = $this->newHandler();
    }

    public function testNoPriorSession_noSessionStart()
    {
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $next = function ($request, $response) {
            return $response;
        };

        $handler = $this->newHandler();
        $response = $handler($request, $response, $next);

        $expect = [];
        $actual = $response->getHeaders();
        $this->assertSame($expect, $actual);
    }

    public function testNoPriorSession_sessionStart()
    {
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $next = function ($request, $response) {
            session_start();
            return $response;
        };

        $handler = $this->newHandler();
        $response = $handler($request, $response, $next);

        $sessionId = session_id();
        $expect = [
            'Set-Cookie' => [
                "PHPSESSID={$sessionId}; path=/",
            ],
            'Expires' => [
                'Thu, 19 Nov 1981 08:52:00 GMT',
            ],
            'Cache-Control' => [
                'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
            ],
            'Pragma' => [
                'no-cache'
            ],
        ];
        $actual = $response->getHeaders();
        $this->assertSame($expect, $actual);

        session_write_close();
    }

    public function testPriorSession_restartWithoutRegenerate()
    {
        // fake a prior session
        session_start();
        $sessionId = session_id();
        session_write_close();

        // now on to "this" session
        $_COOKIE[session_name()] = $sessionId;
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $next = function ($request, $response) {
            session_start();
            return $response;
        };

        $handler = $this->newHandler();
        $response = $handler($request, $response, $next);

        $expect = [
            'Expires' => [
                'Thu, 19 Nov 1981 08:52:00 GMT',
            ],
            'Cache-Control' => [
                'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
            ],
            'Pragma' => [
                'no-cache'
            ],
        ];
        $actual = $response->getHeaders();
        $this->assertSame($expect, $actual);

        session_write_close();
    }


    public function testPriorSession_restartAndRegenerate()
    {
        // fake a prior session
        session_start();
        $sessionId = session_id();
        session_write_close();

        // now on to "this" session
        $_COOKIE[session_name()] = $sessionId;
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $regeneratedId = '';
        $next = function ($request, $response) use (&$regeneratedId) {
            session_start();
            session_regenerate_id();
            $regeneratedId = session_id();
            return $response;
        };

        $handler = $this->newHandler();
        $response = $handler($request, $response, $next);

        $expect = [
            'Set-Cookie' => [
                "PHPSESSID={$regeneratedId}; path=/",
            ],
            'Expires' => [
                'Thu, 19 Nov 1981 08:52:00 GMT',
            ],
            'Cache-Control' => [
                'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
            ],
            'Pragma' => [
                'no-cache'
            ],
        ];
        $actual = $response->getHeaders();
        $this->assertSame($expect, $actual);

        session_write_close();
    }
}
