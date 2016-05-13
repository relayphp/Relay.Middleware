<?php
namespace Relay\Middleware;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use RuntimeException;

class SessionHeadersHandlerTest extends \PHPUnit_Framework_TestCase
{
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
        ini_set('session.cache_limiter', 1);
        $this->setExpectedException(
            RuntimeException::CLASS,
            "The .ini setting 'session.cache_limiter' must be false."
        );
        $handler = $this->newHandler();
    }
}
