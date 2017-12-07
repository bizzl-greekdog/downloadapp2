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


namespace DownloadApp\App\DownloadBundle\Service;


use Doctrine\ORM\EntityManager;
use DownloadApp\App\DownloadBundle\Command\DownloadCommand;
use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\File;
use DownloadApp\App\DownloadBundle\Exceptions\DownloadAlreadyExistsException;
use DownloadApp\App\DownloadBundle\Exceptions\MissingDownloadServiceException;
use DownloadApp\App\UserBundle\Entity\User;
use DownloadApp\App\UserBundle\Service\UserFilesystem;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use JMS\JobQueueBundle\Entity\Job;
use League\Flysystem\Exception;
use League\Flysystem\FilesystemInterface;

/**
 * Class Downloads
 *
 * This service is called to download files, and write metadata files for them.
 *
 * @package Benkle\DownloadApp\DownloadBundle\Service
 */
class Downloads
{
    /** @var FileDownloaderInterface[] */
    private $fileDownloaders = [];

    /** @var  EntityManager */
    private $entityManager;

    /** @var  UserFilesystem */
    private $userFilesystem;

    /**
     * Downloads constructor.
     *
     * @param EntityManager $entityManager
     * @param FilesystemInterface $filesystem
     */
    public function __construct(EntityManager $entityManager, UserFilesystem $userFilesystem)
    {
        $this->entityManager = $entityManager;
        $this->userFilesystem = $userFilesystem;
    }

    /**
     * Set the download service for a given File subclass.
     *
     * @param string $forClass
     * @param FileDownloaderInterface $fileDownloader
     * @return Downloads
     */
    public function setFileDownloader(string $forClass, FileDownloaderInterface $fileDownloader): Downloads
    {
        $this->fileDownloaders[$forClass] = $fileDownloader;
        return $this;
    }

    /**
     * Find a Download by id.
     *
     * @param int $id
     * @return Download
     */
    public function findById(int $id)
    {
        return $this->entityManager->getRepository(Download::class)->find($id);
    }

    /**
     * Find a Download by GUID.
     *
     * @param string $guid
     * @return Download
     */
    public function findByGUID(string $guid)
    {
        return $this->entityManager->getRepository(Download::class)->findOneBy(['guid' => $guid]);
    }

    const FAILED_REQUIRED = 1;
    const FAILED_EXCLUDED = 2;
    const FAILED_ALLOWED  = 3;

    const DOWNLOADED_REQUIRED = 1;
    const DOWNLOADED_EXCLUDED = 2;
    const DOWNLOADED_ALLOWED  = 3;

    /**
     * Get downloads for a user.
     *
     * @param User $user
     * @param int $failed
     * @param int $downloaded
     * @return array|Download[]
     */
    public function findByUser(User $user, int $failed = self::FAILED_ALLOWED, int $downloaded = self::DOWNLOADED_ALLOWED)
    {
        $queryBuilder = $this->createByUserQuery($failed, $downloaded);
        $queryBuilder->select('d');
        return $queryBuilder->getQuery()->setParameter('user', $user)->getResult();
    }

    /**
     * Count downloads for a user.
     *
     * @param User $user
     * @param int $failed
     * @param int $downloaded
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countByUser(User $user, int $failed = self::FAILED_ALLOWED, int $downloaded = self::DOWNLOADED_ALLOWED): int
    {
        $queryBuilder = $this->createByUserQuery($failed, $downloaded);
        $queryBuilder->select($queryBuilder->expr()->count('d'));
        return $queryBuilder->getQuery()->setParameter('user', $user)->getSingleScalarResult();
    }

    /**
     * Download a file entity.
     *
     * @param File $file
     * @param FilesystemInterface $fs
     * @return string The final filename
     * @throws MissingDownloadServiceException
     */
    public function fetch(File $file, FilesystemInterface $fs): string
    {
        $class = get_class($file);
        if (!isset($this->fileDownloaders[$class])) {
            throw new MissingDownloadServiceException($class);
        }
        $this->entityManager->refresh($file);
        return $this->fileDownloaders[$class]->download($file, $fs);
    }

    /**
     * Mark a download as failed.
     *
     * @param Download $download
     * @param \Exception $e
     */
    private function fail(Download $download, \Exception $e)
    {
        $download
            ->setFailed(true)
            ->setError(sprintf('[%s:%d] %s', $e->getFile(), $e->getLine(), $e->getMessage()));
    }

    /**
     * Do the download.
     *
     * @param Download $download
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function download(Download $download)
    {
        try {
            $user = $download->getUser();
            $fs = $this->userFilesystem->get($user);
            $filename = $this->fetch($download->getFile(), $fs);
            $download->setFile($this->entityManager->find(File::class, $download->getFile()->getId()));
            $fs->put("$filename.txt", $download); // TODO Clean comment
            $download->setDownloaded(true);
            $download->setFailed(false);
        } catch (Exception $e) {
            $this->fail($download, $e);
        } catch (MissingDownloadServiceException $e) {
            $this->fail($download, $e);
        } catch (ClientException $e) {
            $this->fail($download, $e);
        } catch (RequestException $e) {
            $this->fail($download, $e);
        }
        $this->entityManager->persist($download);
    }

    /**
     * Persist and schedule a download.
     *
     * @param Download $download
     * @throws DownloadAlreadyExistsException
     */
    public function schedule(Download $download)
    {
        if (
        $this
            ->entityManager
            ->getRepository(Download::class)
            ->findOneBy(['guid' => $download->getGuid()])
        ) {
            throw new DownloadAlreadyExistsException($download->getGuid());
        } else {
            $this->entityManager->persist($download);

            $job = new Job(
                DownloadCommand::NAME,
                [$download->getGuid()],
                true,
                DownloadCommand::QUEUE
            );

            $this->entityManager->persist($job);
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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    function __destruct()
    {
        $this->entityManager->flush();
    }

    /**
     * Create query fpr *byUser methods.
     *
     * @param int $failed
     * @param int $downloaded
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createByUserQuery(int $failed, int $downloaded): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->from('DownloadBundle:Download', 'd');
        $queryBuilder->where('d.user = :user');
        switch ($failed) {
            case self::FAILED_REQUIRED:
                $queryBuilder->andWhere('d.failed = true');
                break;
            case self::FAILED_EXCLUDED:
                $queryBuilder->andWhere('d.failed = false');
                break;
            case self::FAILED_ALLOWED:
            default:
                break;
        }
        switch ($downloaded) {
            case self::DOWNLOADED_REQUIRED:
                $queryBuilder->andWhere('d.downloaded = true');
                break;
            case self::DOWNLOADED_EXCLUDED:
                $queryBuilder->andWhere('d.downloaded = false');
                break;
            case self::DOWNLOADED_ALLOWED:
            default:
                break;
        }
        return $queryBuilder;
    }
}
