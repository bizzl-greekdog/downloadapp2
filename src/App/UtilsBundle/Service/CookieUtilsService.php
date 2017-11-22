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


namespace DownloadApp\App\UtilsBundle\Service;

use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;

/**
 * Class CookieUtilsService
 * @package DownloadApp\App\UtilsBundle\Service
 */
class CookieUtilsService
{
    /**
     * Import cookies created by a chromium extension.
     *
     * @param array $cookies
     * @param CookieJarInterface $cookieJar
     * @return CookieUtilsService
     */
    public function importCookies(array $cookies, CookieJarInterface $cookieJar): CookieUtilsService
    {
        $cookieJar->clear();
        foreach ($cookies as $cookie) {
            $setCookie = new SetCookie();
            $setCookie->setDomain($cookie['domain']);
            $setCookie->setExpires($cookie['expirationDate']);
            $setCookie->setPath($cookie['path']);
            $setCookie->setHttpOnly($cookie['httpOnly']);
            $setCookie->setSecure($cookie['session']);
            $setCookie->setName($cookie['name']);
            $setCookie->setValue($cookie['value']);
            $cookieJar->setCookie($setCookie);
        }
        return $this;
    }
}
