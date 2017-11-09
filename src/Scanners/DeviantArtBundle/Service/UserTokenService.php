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


use DownloadApp\App\UserBundle\Service\CurrentUserService;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Class UserTokenService
 * @package DownloadApp\Scanners\DeviantArtBundle\Service
 */
class UserTokenService implements TokenServiceInterface
{
    /** @var  string */
    private $tokenDir;

    /** @var  CurrentUserService */
    private $currentUserService;

    /**
     * UserTokenService constructor.
     * @param string $tokenDir
     * @param CurrentUserService $currentUserService
     */
    public function __construct($tokenDir, CurrentUserService $currentUserService)
    {
        $this->tokenDir = $tokenDir;
        $this->currentUserService = $currentUserService;
    }

    /**
     * Get an access token.
     *
     * @return AccessToken|null
     */
    public function getToken()
    {
        $tokenFilePath = $this->createTokenFilePath();
        if (file_exists($tokenFilePath)) {
            $tokenFileContent = json_decode(file_get_contents($tokenFilePath), true);
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
        $tokenFilename = $this->createTokenFilePath();
        $tokenDirectory = dirname($tokenFilename);
        if (!is_dir($tokenDirectory)) {
            mkdir($tokenDirectory, 0755, true);
        }
        file_put_contents($tokenFilename, \GuzzleHttp\json_encode($token));
        return $this;
    }

    /**
     * Create the absolute token path.
     *
     * @return string
     */
    private function createTokenFilePath(): string
    {
        return $this->join(
            $this->tokenDir,
            $this->currentUserService->getUser()->getUsernameCanonical() . '.json'
        );
    }

    /**
     * Join elements into a coherent path.
     *
     * @param \string[] ...$parts
     * @return string
     *
     * TODO Move to it's own service (PathUtilService?)
     */
    private function join(string ...$parts): string
    {
        $result = array_shift($parts);
        foreach ($parts as $part) {
            $lastChar = substr($result, -1);
            $result .= ($lastChar === '/' ? '' : '/') . $part;
        }
        return $result;
    }
}
