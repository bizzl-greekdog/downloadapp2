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


namespace DownloadApp\Scanners\WeasylBundle\Contractor;


use Doctrine\ORM\EntityManager;
use DownloadApp\App\UserBundle\Service\CurrentUser;
use DownloadApp\Scanners\CoreBundle\Contractor\ContractorInterface;
use DownloadApp\Scanners\CoreBundle\Service\ScanScheduler;
use League\Uri\Uri;

/**
 * Class Contractor
 * @package DownloadApp\Scanners\WeasylBundle\Contractor
 */
class Contractor implements ContractorInterface
{
    /** @var  EntityManager */
    private $em;

    /** @var  CurrentUser */
    private $currentUser;

    /** @var  ScanScheduler */
    private $scanScheduler;

    /**
     * Contractor constructor.
     *
     * @param ScanScheduler $scanScheduler
     */
    public function __construct(ScanScheduler $scanScheduler)
    {
        $this->scanScheduler = $scanScheduler;
    }

    /**
     * Contract a scan job.
     *
     * @param string $url
     * @param string|null $referer
     * @return bool
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function contractScan(string $url, string $referer = null): bool
    {
        $sourceUri = Uri::createFromString($url);
        $refererUri = Uri::createFromString($referer);

        /** @var Uri $uri */
        foreach ([$sourceUri, $refererUri] as $uri) {
            if (preg_match('/^([^.]+\.)?weasyl\.com$/', $uri->getHost())) {
                $this->scanScheduler->scheduleScan($url);
                return true;
            }
        }

        return false;
    }

    /**
     * Contract a watchlist scan.
     *
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function contractWatchlist()
    {
        $this->scanScheduler->scheduleWatchlist();
    }
}
