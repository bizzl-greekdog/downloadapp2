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


use Benkle\Deviantart\Api;
use SeinopSys\OAuth2\Client\Provider\DeviantArtProvider;

/**
 * Class ApiProvider
 * @package DownloadApp\Scanners\DeviantArtBundle\Service
 */
class ApiProvider
{
    /** @var  DeviantArtProvider */
    private $provider;

    /** @var  TokenProviderInterface */
    private $tokenService;

    /** @var  Api */
    private $api;

    /**
     * ApiProvider constructor.
     *
     * @param string $id
     * @param string $secret
     * @param string $redirectUri
     * @param TokenProviderInterface $tokenService
     */
    public function __construct(
        string $id,
        string $secret,
        string $redirectUri,
        TokenProviderInterface $tokenService
    )
    {
        $this->provider = new DeviantArtProvider(
            [
                'clientId'     => $id,
                'clientSecret' => $secret,
                'redirectUri'  => $redirectUri,
            ]
        );
        $this->tokenService = $tokenService;
    }

    /**
     * Initialize and authorize the API for later use.
     *
     * @param string|null $authCode
     */
    public function initialize(string $authCode = null)
    {
        if (!isset($this->api)) {
            $this->api = new Api($this->provider, $this->tokenService->getToken());
            $this->api->authorize(
                [Api::SCOPE_STASH, Api::SCOPE_BROWSE, Api::SCOPE_FEED],
                $authCode
            );
        }
    }

    /**
     * PHP 5 introduces a destructor concept similar to that of other object-oriented languages, such as C++.
     * The destructor method will be called as soon as all references to a particular object are removed or
     * when the object is explicitly destroyed or in any order in shutdown sequence.
     *
     * Like constructors, parent destructors will not be called implicitly by the engine.
     * In order to run a parent destructor, one would have to explicitly call parent::__destruct() in the destructor body.
     *
     * Note: Destructors called during the script shutdown have HTTP headers already sent.
     * The working directory in the script shutdown phase can be different with some SAPIs (e.g. Apache).
     *
     * Note: Attempting to throw an exception from a destructor (called in the time of script termination) causes a fatal error.
     *
     * @return void
     * @link http://php.net/manual/en/language.oop5.decon.php
     */
    function __destruct()
    {
        if (isset($this->api) && $this->api->hasAccessToken()) {
            $this->tokenService->setToken($this->api->getAccessToken());
        }
    }

    /**
     * Get the API (and initialize if necessary).
     * @return Api
     */
    public function getApi(): Api
    {
        $this->initialize();
        return $this->api;
    }
}
