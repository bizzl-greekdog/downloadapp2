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
use DownloadApp\App\UserBundle\Service\UserFilesystem;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use JMS\JobQueueBundle\Entity\Job;
use League\Flysystem\Exception;
use League\Flysystem\FilesystemInterface;

/**
 * Class Downloader
 *
 * This service is called to download files, and write metadata files for them.
 *
 * @package Benkle\DownloadApp\DownloadBundle\Service
 */
class Downloader
{
    /** @var FileDownloaderInterface[] */
    private $fileDownloaders = [];

    /** @var  EntityManager */
    private $entityManager;

    /** @var  UserFilesystem */
    private $filesystemService;

    /**
     * Downloader constructor.
     *
     * @param EntityManager $entityManager
     * @param FilesystemInterface $filesystem
     */
    public function __construct(EntityManager $entityManager, UserFilesystem $filesystemService)
    {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
    }

    /**
     * Set the download service for a given File subclass.
     *
     * @param string $forClass
     * @param FileDownloaderInterface $service
     * @return Downloader
     */
    public function setFileDownloader(string $forClass, FileDownloaderInterface $service): Downloader
    {
        $this->fileDownloaders[$forClass] = $service;
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
     */
    public function download(Download $download)
    {
        try {
            $user = $download->getUser();
            $fs = $this->filesystemService->get($user);
            $filename = $this->fetch($download->getFile(), $fs);
            $download->setFile($this->entityManager->find(File::class, $download->getFile()->getId()));
            $fs->put("$filename.txt", $download);
            $download->setDownloaded(true);
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
     */
    function __destruct()
    {
        $this->entityManager->flush();
    }
}
