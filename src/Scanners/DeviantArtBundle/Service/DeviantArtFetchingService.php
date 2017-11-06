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


namespace DownloadApp\Scanners\DeviantArtBundle\Service;


use Benkle\Deviantart\Api;
use Benkle\Deviantart\Exceptions\ApiException;
use Doctrine\ORM\EntityManager;
use DownloadApp\App\DownloadBundle\Command\DownloadCommand;
use DownloadApp\App\DownloadBundle\Entity\ContentFile;
use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\File;
use DownloadApp\App\DownloadBundle\Entity\RemoteFile;
use DownloadApp\App\DownloadBundle\Exceptions\DownloadAlreadyExistsException;
use DownloadApp\App\DownloadBundle\Service\DownloadService;
use DownloadApp\App\UserBundle\Service\CurrentUserService;
use DownloadApp\Scanners\DeviantArtBundle\Command\ScanCommand;
use DownloadApp\Scanners\DeviantArtBundle\Exception\NotADeviantArtPageException;
use GuzzleHttp\Client;
use JMS\JobQueueBundle\Entity\Job;
use League\Uri\Uri;

/**
 * Class DeviantArtFetchingService
 * @package DownloadApp\Scanners\DeviantArtBundle\Service
 */
class DeviantArtFetchingService
{
    const QUEUE = 'scanners.deviantart';

    /** @var  ApiService */
    private $api;

    /** @var  Client */
    private $client;

    /** @var  EntityManager */
    private $entityManager;

    /** @var  CurrentUserService */
    private $currentUserService;

    /** @var  DownloadService */
    private $downloadService;

    /**
     * DeviantArtFetchingService constructor.
     *
     * @param ApiService $api
     * @param Client $client
     * @param EntityManager $entityManager
     * @param CurrentUserService $currentUserService
     * @param DownloadService $downloadService
     */
    public function __construct(
        ApiService $api,
        Client $client,
        EntityManager $entityManager,
        CurrentUserService $currentUserService,
        DownloadService $downloadService
    )
    {
        $this->api = $api;
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->currentUserService = $currentUserService;
        $this->downloadService = $downloadService;
    }

    /**
     * Extract an App URL from a website.
     *
     * @param string $url
     * @return string
     * @throws NotADeviantArtPageException
     */
    public function getAppUrl(string $url): string
    {
        if (strtolower(substr($url, 0, 13)) == 'deviantart://') {
            return $url;
        }
        $response = $this->client->get($url);
        $matches = [];
        preg_match(
            '/<meta.*?name="apple-itunes-app".*?>/',
            $response->getBody()->getContents(),
            $matches
        );

        if (empty($matches)) {
            throw new NotADeviantArtPageException($url);
        }

        preg_match(
            '/app-argument=DeviantArt.*?(?="|, )/i',
            $matches[0],
            $matches
        );

        if (empty($matches)) {
            throw new NotADeviantArtPageException($url);
        }

        return substr($matches[0], 13);
    }

    /**
     * Prepare a remote file for download.
     *
     * @param string $downloadUrl
     * @param string $referer
     * @param string $username
     * @param string $origFilename
     * @param File $file
     */
    private function prepareRemoteFile(string $downloadUrl, string $referer, string $username, string &$origFilename, &$file)
    {
        $origFilename = basename($downloadUrl);
        $filename = $this->modifyFilename($origFilename, $username);
        $file = new RemoteFile();
        $file
            ->setFilename($filename)
            ->setReferer($referer)
            ->setUrl($downloadUrl);
    }

    /**
     * Modify the filename to be more unique and better filterable.
     *
     * @param string $filename
     * @param string $username
     * @return string
     */
    private function modifyFilename(string $filename, string $username): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        return "deviantart_{$basename}_by_{$username}.{$extension}";
    }

    /**
     * Schedule a scan.
     *
     * @param string $command
     * @param string $url
     */
    private function scheduleScan(string $command, string $url)
    {
        $job = new Job(
            $command,
            [$this->currentUserService->getUser()->getUsernameCanonical(), $url],
            true,
            self::QUEUE
        );
        $job->setMaxRetries(1024);
        $this->entityManager->persist($job);
    }

    /**
     * Fetch a deviation.
     *
     * @param string $deviationId
     * @throws DownloadAlreadyExistsException
     */
    public function fetchDeviation(string $deviationId)
    {
        $guid = implode(
            ':', [
                   'deviantart',
                   'deviation',
                   $this->currentUserService->getUser()->getUsernameCanonical(),
                   $deviationId,
               ]
        );

        if ($this->downloadService->findByGUID($guid)) {
            return;
        }

        $download = new Download($guid);
        $origFilename = '';
        $file = null;

        $deviation = $this->api->getApi()->deviation();
        $deviationBaseData = $deviation->getDeviation($deviationId);
        $deviationMetaData = $deviation
                                 ->getMetadata(
                                     [$deviationId],
                                     null,
                                     null,
                                     null,
                                     null,
                                     true
                                 )
                                 ->metadata[0];

        $username = $deviationMetaData->author->username;

        try {
            $deviationDownloadData = $deviation->getDownloadInfo($deviationId, true);
            $downloadUrl = preg_replace('/\?.*$/', '', $deviationDownloadData->src);
            $this->prepareRemoteFile($downloadUrl, $deviationBaseData->url, $username, $origFilename, $file);
        } catch (ApiException $e) {
            if (isset($deviationBaseData->flash)) {
                $this->prepareRemoteFile($deviationBaseData->flash->src, $deviationBaseData->url, $username, $origFilename, $file);
            } elseif (isset($deviationBaseData->videos)) {
                $x = count($deviationBaseData->videos);
            } elseif (isset($deviationBaseData->content)) {
                $this->prepareRemoteFile($deviationBaseData->content->src, $deviationBaseData->url, $username, $origFilename, $file);
            } else {
                $content = $deviation->getContent($deviationId, true);
                $body = sprintf(
                    '<html><head><title>%s</title><style>%s</style></head><body>%s</body></html>',
                    $deviationMetaData->title,
                    $content->css ?? '',
                    $content->html
                );
                $origFilename = preg_replace('/[^ a-zA-Z0-9]/', '', $deviationMetaData->title);
                $origFilename = str_replace(' ', '_', $origFilename);
                $origFilename = strtolower($origFilename) . '.html';
                $file = new ContentFile();
                $file
                    ->setContent($body)
                    ->setFilename($this->modifyFilename($origFilename, $username));
            }
        }


        $download
            ->setComment($deviationMetaData->description)
            ->setMetadata(
                [
                    'Artist'            => $username,
                    'Title'             => $deviationMetaData->title,
                    'Source'            => $deviationBaseData->url,
                    'Original Filename' => $origFilename,
                ]
            )
            ->setFile($file)
            ->setUser($this->currentUserService->getUser());

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
     * Fetch a collection and schedule scans of it's deviations.
     *
     * @param string $collectionId
     * @param string|null $username
     * @param int $offset
     * @throws ApiException
     */
    public function fetchCollection(string $collectionId, string $username = null, int $offset = 0)
    {
        $collection = $this->api->getApi()->collections();
        try {
            do {
                $response = $collection->getFolder($collectionId, $username, $offset, 20, true);
                foreach ($response->results as $result) {
                    $this->scheduleScan(
                        ScanCommand::NAME,
                        "DeviantArt://deviation/{$result->deviationid}"
                    );
                }
                $offset = $response->next_offset;
                if ($offset % 100 === 0) {
                    sleep(4);
                }
            } while ($response->has_more);
        } catch (ApiException $e) {
            if ($e->getCode() == 403) {
                sleep(10);
                $this->fetchCollection($collectionId, $username, $offset);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Fetch a gallery and schedule scans for it's deviations.
     *
     * @param string $galleryId
     * @param string|null $username
     * @param int $offset
     * @throws ApiException
     */
    public function fetchGallery(string $galleryId, string $username = null, int $offset = 0)
    {
        $gallery = $this->api->getApi()->gallery();
        try {
            do {
                $response = $gallery->getFolder(
                    $galleryId,
                    $username,
                    Api::FOLDER_MODE_NEWEST,
                    $offset,
                    20,
                    true
                );
                foreach ($response->results as $result) {
                    $this->scheduleScan(
                        ScanCommand::NAME,
                        "DeviantArt://deviation/{$result->deviationid}"
                    );
                }
                $offset = $response->next_offset;
                if ($offset % 100 === 0) {
                    sleep(4);
                }
            } while ($response->has_more);
        } catch (ApiException $e) {
            if ($e->getCode() == 403) {
                sleep(10);
                $this->fetchGallery($galleryId, $username, $offset);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Fetch a user profile and schedule scans for it's galleries.
     *
     * @param string $username
     */
    public function fetchProfile(string $username)
    {
        $response = $this->api->getApi()->user()->getProfile($username, true, true, true);
        foreach ($response->galleries as $gallery) {
            $this->scheduleScan(
                ScanCommand::NAME,
                "DeviantArt://gallery/{$username}/{$gallery->folderid}"
            );
        }
    }

    /**
     * Fetch data from an app url.
     *
     * @param string $appUrl
     * @throws \Exception
     */
    public function fetchFromAppUrl(string $appUrl)
    {
        $uri = Uri::createFromString($this->getAppUrl($appUrl));
        switch ($uri->getHost()) {
            case 'deviation':
                $this->fetchDeviation(substr($uri->getPath(), 1));
                break;
            case 'collection':
                list($username, $id) = array_values(
                    array_filter(explode('/', $uri->getPath()))
                );
                $this->fetchCollection($id, $username);
                break;
            case 'gallery':
                list($username, $id) = array_values(
                    array_filter(explode('/', $uri->getPath()))
                );
                $this->fetchGallery($id, $username);
                break;
            case 'profile':
                $this->fetchProfile(substr($uri->getPath(), 1));
                break;
            default:
                throw new NotADeviantArtPageException($appUrl);
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
