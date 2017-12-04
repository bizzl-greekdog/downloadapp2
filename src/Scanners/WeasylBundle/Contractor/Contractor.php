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
use DownloadApp\Scanners\WeasylBundle\Command\ScanCommand;
use DownloadApp\Scanners\WeasylBundle\Command\WatchlistCommand;
use DownloadApp\Scanners\WeasylBundle\Service\Scanner;
use JMS\JobQueueBundle\Entity\Job;
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

    /**
     * Contractor constructor.
     * @param EntityManager $em
     * @param CurrentUser $currentUser
     */
    public function __construct(EntityManager $em, CurrentUser $currentUser)
    {
        $this->em = $em;
        $this->currentUser = $currentUser;
    }

    /**
     * Contract a scan job.
     *
     * @param string $url
     * @param string|null $referer
     * @return bool
     */
    public function contract(string $url, string $referer = null): bool
    {
        $sourceUri = Uri::createFromString($url);
        $refererUri = Uri::createFromString($referer);

        /** @var Uri $uri */
        foreach ([$sourceUri, $refererUri] as $uri) {
            if (preg_match('/^([^.]+\.)?weasyl\.com$/', $uri->getHost())) {
                $this->em->persist(new Job(ScanCommand::NAME, [$this->currentUser->get()->getUsernameCanonical(), "$uri"], true, Scanner::QUEUE));
                return true;
            }
        }

        return false;
    }

    /**
     * Contract a watchlist scan.
     */
    public function contractWatchlist()
    {
        $this->em->persist(new Job(WatchlistCommand::NAME, [$this->currentUser->get()], Scanner::QUEUE));
    }
}
