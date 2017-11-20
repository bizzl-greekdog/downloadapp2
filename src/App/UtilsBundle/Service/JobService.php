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

use Doctrine\ORM\EntityManager;
use DownloadApp\App\UtilsBundle\Command\IdleCommand;
use JMS\JobQueueBundle\Entity\Job;

/**
 * Class JobService
 * @package DownloadApp\App\UtilsBundle\Service
 */
class JobService
{
    /** @var  EntityManager */
    private $em;

    /**
     * JobService constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Find a job for a given ID.
     *
     * @param int $id
     * @return Job|null
     */
    public function find(int $id)
    {
        return $this->em->find(Job::class, $id);
    }

    /**
     * Create a rescheduled job.
     *
     * @param Job $job
     * @param string $delay
     * @param bool $autoPersist
     * @return Job
     */
    public function reschedule(Job $job, string $delay, bool $autoPersist = false): Job
    {
        $result = clone $job;
        foreach ($job->getRelatedEntities() as $relatedEntity) {
            $result->addRelatedEntity($relatedEntity);
        }
        $result->setOriginalJob($job);
        $result->setExecuteAfter(new \DateTime($delay));
        if ($autoPersist) {
            $this->persist($job);
        }
        return $result;
    }

    /**
     * Persist a given job.
     *
     * @param Job $job
     * @return JobService
     */
    public function persist(Job $job): JobService
    {
        $this->em->persist($job);
        return $this;
    }

    /**
     * Schedule an idle job to a queue.
     * This really only works with single execution queues.
     *
     * @param string $queue
     * @param int $seconds
     * @param bool $autoPersist
     * @return Job
     */
    public function pauseQueue(string $queue, int $seconds, bool $autoPersist = false): Job
    {
        $job = new Job(IdleCommand::NAME, [$seconds], true, $queue, Job::PRIORITY_HIGH);
        if ($autoPersist) {
            $this->persist($job);
        }
        return $job;
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
        $this->em->flush();
    }


}
