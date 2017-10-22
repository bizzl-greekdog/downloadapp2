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


namespace DownloadApp\Scanners\DeviantArtBundle\Service;


use League\OAuth2\Client\Token\AccessToken;

/**
 * Class SimpleFileTokenService
 * @package DownloadApp\Scanners\DeviantArtBundle\Service
 */
class SimpleFileTokenService implements TokenServiceInterface
{
    /** @var string */
    private $tokenFile;

    /**
     * SimpleFileTokenService constructor.
     *
     * @param string $tokenFile
     */
    public function __construct($tokenFile)
    {
        $this->tokenFile = $tokenFile;
    }

    /**
     * Get an access token.
     *
     * @return AccessToken|null
     */
    public function getToken()
    {
        if (file_exists($this->tokenFile)) {
            $tokenFileContent = json_decode(file_get_contents($this->tokenFile), true);
            return new AccessToken($tokenFileContent);
        }
        return null;
    }

    /**
     * Save an access token.
     *
     * @param AccessToken $token
     * @return $this
     */
    public function setToken(AccessToken $token): TokenServiceInterface
    {
        file_put_contents($this->tokenFile, \GuzzleHttp\json_encode($token));
        return $this;
    }
}
