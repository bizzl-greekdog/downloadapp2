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


use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\File;
use DownloadApp\App\DownloadBundle\Exceptions\MissingDownloadServiceException;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

/**
 * Class DownloadService
 *
 * This service is called to download files, and write metadata files for them.
 *
 * @package Benkle\DownloadApp\DownloadBundle\Service
 */
class DownloadService
{
    /** @var FileDownloadServiceInterface[] */
    private $fileDownloadServices = [];

    /** @var  EntityManager */
    private $entityManager;

    /**
     * Get the entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Set the entity manager.
     *
     * @param EntityManager $entityManager
     * @return $this
     */
    public function setEntityManager(EntityManager $entityManager): DownloadService
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * Expand a file entity.
     *
     * Doctrine inheritance only fetches data according to the class requested, and don't expand when we actually
     * get a child class.
     *
     * @TODO Move this to my adoption bundles.
     *
     * @param File $file
     * @return File
     */
    private function expandFileEntity(File $file): File
    {
        $this->entityManager->detach($file);
        /** @var File $file */
        $file = $this->entityManager
            ->getRepository(get_class($file))
            ->find($file->getId());
        return $file;
    }

    /**
     * Set the download service for a given File subclass.
     *
     * @param string $forClass
     * @param FileDownloadServiceInterface $service
     * @return DownloadService
     */
    public function setFileDownloadService(string $forClass, FileDownloadServiceInterface $service): DownloadService
    {
        $this->fileDownloadServices[$forClass] = $service;
        return $this;
    }

    /**
     * Download a file entity.
     *
     * @param File $file
     * @param FilesystemInterface $fs
     * @return string The final filename
     * @throws MissingDownloadServiceException
     */
    public function fetchFile(File $file, FilesystemInterface $fs): string
    {
        $class = get_class($file);
        if (!isset($this->fileDownloadServices[$class])) {
            throw new MissingDownloadServiceException($class);
        }
        $file = $this->expandFileEntity($file);
        return $this->fileDownloadServices[$class]->download($file, $fs);
    }

    /**
     * Mark a download as failed.
     *
     * @param Download $download
     * @param \Exception $e
     */
    private function failDownload(Download $download, \Exception $e)
    {
        $download
            ->setFailed(true)
            ->setError($e);
    }

    /**
     * Do the download.
     *
     * @param Download $download
     */
    public function download(Download $download)
    {
        try {
            $fs = new Filesystem(new Local('/home/bizzl'));
            $filename = $this->fetchFile($download->getFile(), $fs);
            $fs->put("$filename.txt", $download);
            $download->setDownloaded(true);
        } catch (Exception $e) {
            $this->failDownload($download, $e);
        } catch (MissingDownloadServiceException $e) {
            $this->failDownload($download, $e);
        } catch (ClientException $e) {
            $this->failDownload($download, $e);
        } catch (RequestException $e) {
            $this->failDownload($download, $e);
        }
        $this->entityManager->persist($download);
    }
}
