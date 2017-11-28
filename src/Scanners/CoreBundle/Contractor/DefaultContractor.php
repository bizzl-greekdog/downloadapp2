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


namespace DownloadApp\Scanners\CoreBundle\Contractor;

use DownloadApp\App\UserBundle\Service\CurrentUser;
use DownloadApp\App\UtilsBundle\Service\Jobs;
use DownloadApp\Scanners\CoreBundle\Command\DefaultScanCommand;
use JMS\JobQueueBundle\Entity\Job;

/**
 * Class DefaultContractor
 * @package DownloadApp\Scanners\CoreBundle\Contractor
 */
class DefaultContractor implements ContractorInterface
{
    /** @var  Jobs */
    private $jobs;

    /** @var  CurrentUser */
    private $currentUser;

    /**
     * DefaultContractor constructor.
     * @param Jobs $jobs
     * @param CurrentUser $currentUser
     */
    public function __construct(Jobs $jobs, CurrentUser $currentUser)
    {
        $this->jobs = $jobs;
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
        $job = new Job(DefaultScanCommand::NAME, [$url, $referer]);
        $this->jobs->persist($job);
        return true;
    }

    /**
     * Contract a watchlist scan.
     */
    public function contractWatchlist()
    {
        return;
    }
}
