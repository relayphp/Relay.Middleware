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

/**
 *
 * Sends the session headers in the Response.
 *
 * This works correctly only if you have these settings:
 *
 * ```
 * ini_set('session.use_trans_sid', false);
 * ini_set('session.use_cookies', false);
 * ini_set('session.use_only_cookies', true);
 * ```
 *
 * @todo http://php.net/manual/en/function.session-cache-limiter.php
 *
 * @todo http://php.net/manual/en/function.session-cache-expire.php
 *
 * @package Relay.Middleware
 *
 */
class SessionHeadersHandler
{
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
            // one of the middlewares changed it.
            // capture any session name changes as well.
            $newName = session_name();
            $response = $response->withAddedHeader(
                'Set-Cookie',
                $this->newSessionCookie($newName, $newId)
            );
        }

        // done!
        return $response;
    }

    /**
     *
     * Returns a new session cookie header value.
     *
     * @see https://github.com/php/php-src/blob/PHP-5.6.20/ext/session/session.c#L1337-L1407
     *
     * @return string
     *
     */
    protected function newSessionCookie($newName, $newId)
    {
        $cookie = urlencode($newName) . '=' . urlencode($newId);

        // $lifetime, $path, $domain, $secure, $httponly
        extract(session_get_cookie_params());

        if ($lifetime) {
            $expires = gmdate('D, d M Y H:i:s T', time() + $lifetime);
            $cookie .= "; expires={$expires}; max-age={$lifetime}";
        }

        if ($domain) {
            $cookie .= "; domain={$domain}";
        }

        if ($path) {
            $cookie .= "; path={$path}";
        }

        if ($secure) {
            $cookie .= '; secure';
        }

        if ($httponly) {
            $cookie .= '; httponly';
        }

        return $cookie;
    }
}
