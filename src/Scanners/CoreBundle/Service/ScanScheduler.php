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


namespace DownloadApp\Scanners\CoreBundle\Service;

use Doctrine\ORM\EntityManager;
use DownloadApp\App\UserBundle\Exception\NoLoggedInUserException;
use DownloadApp\App\UserBundle\Service\CurrentUser;
use JMS\JobQueueBundle\Entity\Job;

/**
 * Class ScanScheduler
 * @package DownloadApp\Scanners\CoreBundle\Service
 */
class ScanScheduler
{
    /** @var  CurrentUser */
    private $currentUser;

    /** @var  EntityManager */
    private $entityManager;

    /** @var  string */
    private $commandName;

    /** @var  string */
    private $watchlistCommand;

    /** @var  string */
    private $queue;

    /**
     * ScanScheduler constructor.
     *
     * @param CurrentUser $currentUser
     * @param EntityManager $entityManager
     * @param string $commandName
     * @param string $queue
     */
    public function __construct(CurrentUser $currentUser, EntityManager $entityManager, string $commandName, string $watchlistCommand, string $queue)
    {
        $this->currentUser = $currentUser;
        $this->entityManager = $entityManager;
        $this->commandName = $commandName;
        $this->watchlistCommand = $watchlistCommand;
        $this->queue = $queue;
    }

    /**
     * Schedule a scan.
     *
     * @param string $url
     * @param string|null $referer
     * @internal param string $command
     * @throws NoLoggedInUserException
     */
    public function scheduleScan(string $url, string $referer = null)
    {
        $user = $this->currentUser->get();
        $job = new Job(
            $this->commandName,
            [$user->getUsernameCanonical(), $url],
            true,
            $this->queue
        );
        $job->setMaxRetries(1024);
        $job->addRelatedEntity($user);
        $this->entityManager->persist($job);
    }

    /**
     * Schedule a scan of the watchlist.
     *
     * @throws NoLoggedInUserException
     */
    public function scheduleWatchlist()
    {
        $user = $this->currentUser->get();
        $job = new Job(
            $this->watchlistCommand,
            [$user->getUsernameCanonical()],
            true,
            $this->queue
        );
        $job->setMaxRetries(1024);
        $job->addRelatedEntity($user);
        $this->entityManager->persist($job);
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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    function __destruct()
    {
        $this->entityManager->flush();
    }


}
