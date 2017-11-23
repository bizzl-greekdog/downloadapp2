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


namespace DownloadApp\Scanners\FurAffinityBundle\Service;


use Doctrine\ORM\EntityManager;
use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\RemoteFile;
use DownloadApp\App\DownloadBundle\Exceptions\DownloadAlreadyExistsException;
use DownloadApp\App\DownloadBundle\Service\DownloadService;
use DownloadApp\App\UserBundle\Service\CurrentUserService;
use GuzzleHttp\Client;
use League\Uri\Uri;
use PHPHtmlParser\Dom;

/**
 * Class FetchingService
 * @package DownloadApp\Scanners\FurAffinityBundle\Service
 */
class FetchingService
{
    /** @var  Client */
    private $client;

    /** @var  EntityManager */
    private $entityManager;

    /** @var  CurrentUserService */
    private $currentUserService;

    /** @var  DownloadService */
    private $downloadService;

    /**
     * FetchingService constructor.
     * @param Client $client
     * @param EntityManager $entityManager
     * @param CurrentUserService $currentUserService
     * @param DownloadService $downloadService
     */
    public function __construct(Client $client, EntityManager $entityManager, CurrentUserService $currentUserService, DownloadService $downloadService)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->currentUserService = $currentUserService;
        $this->downloadService = $downloadService;
    }

    /**
     * Fetch a single submission.
     *
     * @param string $url
     * @throws DownloadAlreadyExistsException
     */
    public function fetchSubmission(string $url)
    {
        $uri = Uri::createFromString($url);
        $path = array_filter(explode('/', $uri->getPath()));
        $guid = implode(
            ':', [
                   'furaffinity',
                   'submission',
                   $this->currentUserService->getUser()->getUsernameCanonical(),
                   $path[1],
               ]
        );

        if ($this->downloadService->findByGUID($guid)) {
            return;
        }

        $response = $this->client->get($url);

        $dom = new Dom();
        $dom->load($response->getBody()->getContents());

        $fileUrl = $uri->getScheme() . '//' . $dom->find('a[href*=facdn]', 0)->getAttribute('href');
        $filename = basename($fileUrl);
        $title = $dom->find('#page-submission td.cat b', 0)->innerHtml;
        $artist = strip_tags($dom->find('#page-submission td.cat a[href*=user]', 0)->innerHtml);
        $description = $dom->find('#page-submission td.alt1[width="70%"]', 0)->innerHtml;

        $file = new RemoteFile();
        $file
            ->setFilename($filename)
            ->setUrl($fileUrl)
            ->setReferer($url);
        $download = new Download($guid);
        $download
            ->setComment($description)
            ->setMetadata(
                [
                    'Artist'            => $artist,
                    'Title'             => $title,
                    'Source'            => $url,
                    'Original Filename' => $filename,
                ]
            )
            ->setFile($file)
            ->setUser($this->currentUserService->getUser());

        $this->downloadService->scheduleDownload($download);
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
