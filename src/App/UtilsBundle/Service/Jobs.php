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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\TransactionRequiredException;
use DownloadApp\App\UserBundle\Entity\User;
use DownloadApp\App\UtilsBundle\Command\IdleCommand;
use JMS\JobQueueBundle\Entity\Job;

/**
 * Class Jobs
 * @package DownloadApp\App\UtilsBundle\Service
 */
class Jobs
{
    /** @var  EntityManager */
    private $em;

    /**
     * Jobs constructor.
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
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws ORMException
     */
    public function find(int $id)
    {
        return $this->em->find(Job::class, $id);
    }

    /**
     * Find jobs associated with a user.
     *
     * @param User $user
     * @param string[] $states
     * @return Job[]
     */
    public function findJobsForUser(User $user, $states = [])
    {
        $fields = 'j.*';
        $sql = $this->createSQL($user, $states, $fields, $params);
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('JMSJobQueueBundle:Job', 'j');
        return $this->em->createNativeQuery($sql, $rsm)->setParameters($params)->getResult();
    }

    /**
     * Count jobs associated with a user.
     *
     * @param User $user
     * @param string[] $states
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countJobsForUser(User $user, $states = []): int
    {
        $fields = 'COUNT(j.id) AS c';
        $sql = $this->createSQL($user, $states, $fields, $params);
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addEntityResult(Job::class, 'j');
        $rsm->addFieldResult('j', 'c', 'id');
        $result = $this->em->createNativeQuery($sql, $rsm)->setParameters($params);
        return intval($result->getSingleScalarResult(), 10);
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
     * @return Jobs
     */
    public function persist(Job $job): Jobs
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
     * @throws OptimisticLockException
     */
    function __destruct()
    {
        $this->em->flush();
    }

    /**
     * Create a query to relate users and jobs.
     *
     * @param User $user
     * @param $states
     * @param $fields
     * @param $params
     * @return string
     */
    private function createSQL(User $user, $states, $fields, &$params): string
    {
        $params = new ArrayCollection();
        $params->add(new Parameter('related_id', json_encode(['id' => $user->getId()])));
        $params->add(new Parameter('related_class', User::class));
        $sql = "SELECT $fields FROM jms_jobs j INNER JOIN jms_job_related_entities r ON r.job_id = j.id WHERE r.related_id = :related_id AND r.related_class = :related_class";
        if (!empty($states)) {
            $sql .= ' AND j.state IN (:states)';
            $params->add(new Parameter('states', $states, Connection::PARAM_STR_ARRAY));
        }
        return $sql;
    }
}
