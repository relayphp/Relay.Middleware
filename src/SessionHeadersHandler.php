<?php
/**
 *
 * This file is part of Relay for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Relay\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

/**
 *
 * Sends the session headers in the Response, putting them under manual control
 * rather than relying on PHP to send them itself.
 *
 * This works correctly only if you have these settings:
 *
 * ```
 * ini_set('session.use_trans_sid', false);
 * ini_set('session.use_cookies', false);
 * ini_set('session.use_only_cookies', true);
 * ini_set('session.cache_limiter', '');
 * ```
 *
 * Note that the Last-Modified value will not be the last time the session was
 * saved, but instead the current `time()`.
 *
 * @package Relay.Middleware
 *
 */
class SessionHeadersHandler
{
    const EXPIRED = 'Thu, 19 Nov 1981 08:52:00 GMT';

    protected $cacheLimiter;

    protected $cacheExpire;

    public function __construct($cacheLimiter = 'nocache', $cacheExpire = 180)
    {
        if (ini_get('session.use_trans_sid') != false) {
            $message = "The .ini setting 'session.use_trans_sid' must be false.";
            throw new RuntimeException($message);
        }

        if (ini_get('session.use_cookies') != false) {
            $message = "The .ini setting 'session.use_cookies' must be false.";
            throw new RuntimeException($message);
        }

        if (ini_get('session.use_only_cookies') != true) {
            $message = "The .ini setting 'session.use_only_cookies' must be true.";
            throw new RuntimeException($message);
        }

        if (ini_get('session.cache_limiter') !== '') {
            $message = "The .ini setting 'session.cache_limiter' must be an empty string.";
            throw new RuntimeException($message);
        }

        $this->cacheLimiter = $cacheLimiter;
        $this->cacheExpire = (int) $cacheExpire;
    }

    /**
     *
     * Sends the session headers in the Response.
     *
     * @param Request $request The HTTP request.
     *
     * @param Response $response The HTTP response.
     *
     * @param callable $next The next middleware in the queue.
     *
     * @return Response
     *
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        // retain the incoming session id
        $oldId = '';
        $oldName = session_name();
        $cookies = $request->getCookieParams();
        if (! empty($cookies[$oldName])) {
            $oldId = $cookies[$oldName];
        }

        // invoke the next middleware
        $response = $next($request, $response);

        // is the session id still the same?
        $newId = session_id();
        if ($newId !== $oldId) {
            // one of the middlewares changed it; send the new one.
            // capture any session name changes as well.
            $response = $this->withNewSessionCookie($response, $newId);
        }

        // if there is a session id, also send the cache limiters
        if ($newId) {
            $response = $this->withCacheLimiter($response);
        }

        // done!
        return $response;
    }

    /**
     *
     * Adds a session cookie header to the Response.
     *
     * @see https://github.com/php/php-src/blob/PHP-5.6.20/ext/session/session.c#L1337-L1407
     *
     * @return string
     *
     */
    protected function withNewSessionCookie($response, $sessionId)
    {
        $cookie = urlencode(session_name()) . '=' . urlencode($sessionId);

        $params = session_get_cookie_params();

        if ($params['lifetime']) {
            $expires = $this->timestamp(time() + $params['lifetime']);
            $cookie .= "; expires={$expires}; max-age={$params['lifetime']}";
        }

        if ($params['domain']) {
            $cookie .= "; domain={$params['domain']}";
        }

        if ($params['path']) {
            $cookie .= "; path={$params['path']}";
        }

        if ($params['secure']) {
            $cookie .= '; secure';
        }

        if ($params['httponly']) {
            $cookie .= '; httponly';
        }

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    protected function timestamp($time)
    {
        return gmdate('D, d M Y H:i:s T', $time);
    }

    protected function withCacheLimiter(Response $response)
    {
        switch ($this->cacheLimiter) {
            case 'public':
                return $this->cacheLimiterPublic($response);
            case 'private_no_expire':
                return $this->cacheLimiterPrivateNoExpire($response);
            case 'private':
                return $this->cacheLimiterPrivate($response);
            case 'nocache':
                return $this->cacheLimiterNocache($response);
            default:
                return $response;
        }
    }

    // session.c:1065
    protected function cacheLimiterPublic(Response $response)
    {
        $now = time();
        $maxAge = $this->cacheExpires * 60;
        $expires = $this->timestamp($now + $maxAge);
        $cacheControl = "public, max-age={$maxAge}";
        $lastModified = $this->timestamp($now);

        return $response
            ->withAddedHeader('Expires', $expires)
            ->withAddedHeader('Cache-Control', $cacheControl)
            ->withAddedHeader('Last-Modified', $lastModified);
    }

    // session.c:1084
    protected function cacheLimiterPrivateNoExpire(Response $response)
    {
        $maxAge = $this->cacheExpires * 60;
        $cacheControl = "private, max-age={$maxAge}, pre-check={$maxAge}";
        $lastModified = $this->timestamp(time());

        return $response
            ->withAddedHeader('Cache-Control', $cacheControl)
            ->withAddedHeader('Last-Modified', $lastModified);
    }

    // session.c:1095
    protected function cacheLimiterPrivate(Response $response)
    {
        $response = $response->withAddedHeader('Expires', self::EXPIRED);
        return $response->withCacheLimiterPrivateNoExpire($response);
    }

    // session.c:1102
    protected function cacheLimiterNocache(Response $response)
    {
        return $response
            ->withAddedHeader('Expires', self::EXPIRED)
            ->withAddedHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
            )
            ->withAddedHeader('Pragma', 'no-cache');
    }
}
