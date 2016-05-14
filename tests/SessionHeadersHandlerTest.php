<?php
namespace Relay\Middleware;

use RuntimeException;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Stream;

session_set_cookie_params(
    1,
    '/foo/bar',
    '.example.com',
    true,
    true
);

class SessionHeadersHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $stream;
    protected $time;

    protected function setUp()
    {
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cache_limiter', '');
        $this->time = time();
    }

    protected function newHandler($cacheLimiter)
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
        $handler = $this->newHandler('');
    }

    public function testIni_useCookies()
    {
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_cookies', 1);
        $this->setExpectedException(
            RuntimeException::CLASS,
            "The .ini setting 'session.use_cookies' must be false."
        );
        $handler = $this->newHandler('');
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
        $handler = $this->newHandler('');
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
        $handler = $this->newHandler('');
    }

    protected function timestamp($adj = 0)
    {
        return gmdate('D, d M Y H:i:s T', $this->time + $adj);
    }

    protected function assertSessionCookie(array $headers, $sessionId)
    {
        $time = time();

        $cookie = $headers['Set-Cookie'][0];
        $parts = explode(';', $cookie);

        // PHPSESSID=...
        $expect = session_name() . "={$sessionId}";
        $actual = trim($parts[0]);
        $this->assertSame($expect, $actual);

        // expires=...
        $expect = 'expires=' . $this->timestamp(+1);
        $actual = trim($parts[1]);
        $this->assertSame($expect, $actual);

        // max-age=...
        $expect = 'max-age=1';
        $actual = trim($parts[2]);
        $this->assertSame($expect, $actual);

        // domain
        $expect = 'domain=.example.com';
        $actual = trim($parts[3]);
        $this->assertSame($expect, $actual);

        // path
        $expect = 'path=/foo/bar';
        $actual = trim($parts[4]);
        $this->assertSame($expect, $actual);

        // secure; httponly
        $this->assertSame('secure', trim($parts[5]));
        $this->assertSame('httponly', trim($parts[6]));
    }

    public function testNoPriorSession_noSessionStart()
    {
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $next = function ($request, $response) {
            return $response;
        };

        $handler = $this->newHandler('');
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

        $handler = $this->newHandler('');
        $response = $handler($request, $response, $next);

        $this->assertSessionCookie($response->getHeaders(), session_id());

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

        $handler = $this->newHandler('');
        $response = $handler($request, $response, $next);

        $expect = [];
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

        $handler = $this->newHandler('');
        $response = $handler($request, $response, $next);

        $this->assertSessionCookie($response->getHeaders(), $regeneratedId);

        session_write_close();
    }

    protected function getCacheLimiterHeaders($cacheLimiter)
    {
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $next = function ($request, $response) {
            session_start();
            return $response;
        };

        $handler = $this->newHandler($cacheLimiter);
        $response = $handler($request, $response, $next);

        $headers = $response->getHeaders();
        $this->assertSessionCookie($headers, session_id());
        unset($headers['Set-Cookie']);

        session_write_close();
        return $headers;
    }

    public function testCacheLimiter_public()
    {
        $expect = array(
            'Expires' => array(
                $this->timestamp(+10800),
            ),
            'Cache-Control' => array(
                'public, max-age=10800',
            ),
            'Last-Modified' => array(
                $this->timestamp(),
            ),
        );
        $actual = $this->getCacheLimiterHeaders('public');
        $this->assertSame($expect, $actual);
    }

    public function testCacheLimiter_privateNoExpire()
    {
        $expect = array(
            'Cache-Control' => array(
                'private, max-age=10800, pre-check=10800'
            ),
            'Last-Modified' => array(
                $this->timestamp()
            ),
        );
        $actual = $this->getCacheLimiterHeaders('private_no_expire');
        $this->assertSame($expect, $actual);
    }

    public function testCacheLimiter_private()
    {
        $expect = array(
            'Expires' => [
                'Thu, 19 Nov 1981 08:52:00 GMT',
            ],
            'Cache-Control' => array(
                'private, max-age=10800, pre-check=10800'
            ),
            'Last-Modified' => array(
                $this->timestamp()
            ),
        );
        $actual = $this->getCacheLimiterHeaders('private');
        $this->assertSame($expect, $actual);
    }

    public function testCacheLimiter_nocache()
    {
        $expect = [
            'Expires' => [
                'Thu, 19 Nov 1981 08:52:00 GMT',
            ],
            'Cache-Control' => [
                'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            ],
            'Pragma' => [
                'no-cache',
            ],
        ];
        $actual = $this->getCacheLimiterHeaders('nocache');
        $this->assertSame($expect, $actual);
    }
}
