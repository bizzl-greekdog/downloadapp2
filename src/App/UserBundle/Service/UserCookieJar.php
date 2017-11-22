<?php
/*
 * Copyright (c) 2017 Benjamin Kleiner
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


namespace DownloadApp\App\UserBundle\Service;


use DownloadApp\App\UtilsBundle\Service\PathUtilsService;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Traversable;

/**
 * Class UserCookieJar
 * @package DownloadApp\App\UserBundle\Service
 */
class UserCookieJar implements CookieJarInterface
{
    /** @var  CurrentUserService */
    private $currentUserService;

    /** @var  CookieJarInterface[] */
    private $loadedJars = [];

    /** @var  string */
    private $cookiesDir;

    /** @var  PathUtilsService */
    private $pathUtilsService;

    /**
     * UserCookieJar constructor.
     *
     * @param CurrentUserService $currentUserService
     * @param string $cookiesDir
     * @param PathUtilsService $pathUtilsService
     */
    public function __construct(CurrentUserService $currentUserService, string $cookiesDir, PathUtilsService $pathUtilsService)
    {
        $this->currentUserService = $currentUserService;
        $this->cookiesDir = $cookiesDir;
        $this->pathUtilsService = $pathUtilsService;

        if (!is_dir($this->cookiesDir)) {
            mkdir($this->cookiesDir, 0755, true);
        }
    }

    private function getUserJar(): CookieJarInterface
    {
        $username = $this->currentUserService->getUser()->getUsernameCanonical();
        if (!isset($this->loadedJars[$username])) {
            $this->loadedJars[$username] = new FileCookieJar($this->pathUtilsService->join($this->cookiesDir, "{$username}.cookiejar"), true);
        }
        return $this->loadedJars[$username];
    }

    /**
     * Create a request with added cookie headers.
     *
     * If no matching cookies are found in the cookie jar, then no Cookie
     * header is added to the request and the same request is returned.
     *
     * @param RequestInterface $request Request object to modify.
     *
     * @return RequestInterface returns the modified request.
     */
    public function withCookieHeader(RequestInterface $request)
    {
        return $this->getUserJar()->withCookieHeader($request);
    }

    /**
     * Extract cookies from an HTTP response and store them in the CookieJar.
     *
     * @param RequestInterface $request Request that was sent
     * @param ResponseInterface $response Response that was received
     */
    public function extractCookies(
        RequestInterface $request,
        ResponseInterface $response
    )
    {
        return $this->getUserJar()->extractCookies($request, $response);
    }

    /**
     * Sets a cookie in the cookie jar.
     *
     * @param SetCookie $cookie Cookie to set.
     *
     * @return bool Returns true on success or false on failure
     */
    public function setCookie(SetCookie $cookie)
    {
        return $this->getUserJar()->setCookie($cookie);
    }

    /**
     * Remove cookies currently held in the cookie jar.
     *
     * Invoking this method without arguments will empty the whole cookie jar.
     * If given a $domain argument only cookies belonging to that domain will
     * be removed. If given a $domain and $path argument, cookies belonging to
     * the specified path within that domain are removed. If given all three
     * arguments, then the cookie with the specified name, path and domain is
     * removed.
     *
     * @param string $domain Clears cookies matching a domain
     * @param string $path Clears cookies matching a domain and path
     * @param string $name Clears cookies matching a domain, path, and name
     *
     * @return CookieJarInterface
     */
    public function clear($domain = null, $path = null, $name = null)
    {
        return $this->getUserJar()->clear($domain, $path, $name);
    }

    /**
     * Discard all sessions cookies.
     *
     * Removes cookies that don't have an expire field or a have a discard
     * field set to true. To be called when the user agent shuts down according
     * to RFC 2965.
     */
    public function clearSessionCookies()
    {
        return $this->getUserJar()->clearSessionCookies();
    }

    /**
     * Converts the cookie jar to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getUserJar()->toArray();
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return $this->getUserJar()->getIterator();
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->getUserJar()->count();
    }
}
