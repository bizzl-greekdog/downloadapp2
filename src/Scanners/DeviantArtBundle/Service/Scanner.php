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
use DownloadApp\App\DownloadBundle\Entity\ContentFile;
use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\File;
use DownloadApp\App\DownloadBundle\Entity\RemoteFile;
use DownloadApp\App\DownloadBundle\Exceptions\DownloadAlreadyExistsException;
use DownloadApp\App\DownloadBundle\Service\Downloads;
use DownloadApp\App\UserBundle\Service\CurrentUser;
use DownloadApp\App\UtilsBundle\Service\Notifications;
use DownloadApp\Scanners\CoreBundle\Service\ScanScheduler;
use DownloadApp\Scanners\DeviantArtBundle\Exception\NotADeviantArtPageException;
use GuzzleHttp\Client;
use League\Uri\Uri;

/**
 * Class Scanner
 * @package DownloadApp\Scanners\DeviantArtBundle\Service
 */
class Scanner
{
    const QUEUE = 'scanners.deviantart';

    /** @var  ApiProvider */
    private $apiProvider;

    /** @var  Client */
    private $client;

    /** @var  CurrentUser */
    private $currentUser;

    /** @var  Downloads */
    private $downloader;

    /** @var  ScanScheduler */
    private $scanScheduler;

    /** @var Notifications */
    private $notifications;

    /**
     * Scanner constructor.
     *
     * @param ApiProvider $apiProvider
     * @param Client $client
     * @param CurrentUser $currentUser
     * @param Downloads $downloader
     * @param ScanScheduler $scanScheduler
     * @param Notifications $notifications
     */
    public function __construct(
        ApiProvider $apiProvider,
        Client $client,
        CurrentUser $currentUser,
        Downloads $downloader,
        ScanScheduler $scanScheduler,
        Notifications $notifications
    )
    {
        $this->apiProvider = $apiProvider;
        $this->client = $client;
        $this->currentUser = $currentUser;
        $this->downloader = $downloader;
        $this->scanScheduler = $scanScheduler;
        $this->notifications = $notifications;
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
        $origFilename = basename(parse_url($downloadUrl, PHP_URL_PATH));
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
     * Scan a deviation.
     *
     * @param string $deviationId
     * @throws DownloadAlreadyExistsException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function ScanDeviation(string $deviationId)
    {
        $guid = implode(
            ':', [
                   'deviantart',
                   'deviation',
                   $this->currentUser->get()->getUsernameCanonical(),
                   $deviationId,
               ]
        );

        if ($this->downloader->findByGUID($guid)) {
            return;
        }

        $download = new Download($guid);
        $origFilename = '';
        $file = null;

        $deviation = $this->apiProvider->getApi()->deviation();
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
            ->setUser($this->currentUser->get());

        $this
            ->downloader
            ->persist($download)
            ->schedule($download);

        $this->notifications->log("{$deviationMetaData->title} by {$username} scanned and download scheduled");
    }

    /**
     * Fetch a collection and schedule scans of it's deviations.
     *
     * @param string $collectionId
     * @param string|null $username
     * @param int $offset
     * @param int $total
     * @throws ApiException
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function scanCollection(string $collectionId, string $username = null, int $offset = 0, int $total = 0)
    {
        $collection = $this->apiProvider->getApi()->collections();
        $silentEnd = false;
        try {
            do {
                $response = $collection->getFolder($collectionId, $username, $offset, 20, true);
                foreach ($response->results as $result) {
                    $this->scanScheduler->scheduleScan("DeviantArt://deviation/{$result->deviationid}");
                    $total++;
                }
                $offset = $response->next_offset;
                if ($offset % 100 === 0) {
                    sleep(4);
                }
                $this->notifications->log("scanned up to {$offset} of collection {$collectionId}");
            } while ($response->has_more);
        } catch (ApiException $e) {
            if ($e->getCode() == 403) {
                sleep(10);
                $this->scanCollection($collectionId, $username, $offset, $total);
                $silentEnd = true;
            } else {
                throw $e;
            }
        }
        if (!$silentEnd) {
            $this->notifications->alert("Collection {$collectionId} contained a total of {$total} submissions, scans scheduled");
        }
    }

    /**
     * Fetch a gallery and schedule scans for it's deviations.
     *
     * @param string $galleryId
     * @param string|null $username
     * @param int $offset
     * @param int $total
     * @throws ApiException
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function scanGallery(string $galleryId, string $username = null, int $offset = 0, int $total = 0)
    {
        $gallery = $this->apiProvider->getApi()->gallery();
        $silentEnd = false;
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
                    $this->scanScheduler->scheduleScan("DeviantArt://deviation/{$result->deviationid}");
                    $total++;
                }
                $offset = $response->next_offset;
                if ($offset % 100 === 0) {
                    sleep(4);
                }
                $this->notifications->log("scanned up to {$offset} of gallery {$galleryId}");
            } while ($response->has_more);
        } catch (ApiException $e) {
            if ($e->getCode() == 403) {
                sleep(10);
                $this->scanGallery($galleryId, $username, $offset);
                $silentEnd = true;
            } else {
                throw $e;
            }
        }
        if (!$silentEnd) {
            $this->notifications->alert("Gallery {$galleryId} contained a total of {$total} submissions, scans scheduled");
        }
    }

    /**
     * Scan a user profile and schedule scans for it's galleries.
     *
     * @param string $username
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function scanProfile(string $username)
    {
        $response = $this->apiProvider->getApi()->user()->getProfile($username, true, true, true);
        foreach ($response->galleries as $gallery) {
            $this->scanScheduler->scheduleScan("DeviantArt://gallery/{$username}/{$gallery->folderid}");
        }
    }

    /**
     * Fetch data from an app url.
     *
     * @param string $appUrl
     * @throws ApiException
     * @throws DownloadAlreadyExistsException
     * @throws NotADeviantArtPageException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function scan(string $appUrl)
    {
        $uri = Uri::createFromString($this->getAppUrl($appUrl));
        switch ($uri->getHost()) {
            case 'deviation':
                $this->ScanDeviation(substr($uri->getPath(), 1));
                break;
            case 'collection':
                list($username, $id) = array_values(
                    array_filter(explode('/', $uri->getPath()))
                );
                $this->scanCollection($id, $username);
                break;
            case 'gallery':
                list($username, $id) = array_values(
                    array_filter(explode('/', $uri->getPath()))
                );
                $this->scanGallery($id, $username);
                break;
            case 'profile':
                $this->scanProfile(substr($uri->getPath(), 1));
                break;
            default:
                throw new NotADeviantArtPageException($appUrl);
        }
    }

    /**
     * Fetch the users watch list and schedule scans for deviantions.
     *
     * @param string|null $cursor
     * @param int $total
     * @throws ApiException
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function scanWatchlist(string $cursor = null, int $total = 0)
    {
        $feed = $this->apiProvider->getApi()->feed();
        $iteration = 0;
        $silentEnd = false;
        try {
            do {
                $response = $feed->getHome($cursor, true);
                $iteration++;
                $cursor = $response->cursor;
                foreach ($response->items as $item) {
                    if ($item->type === 'deviation_submitted') {
                        foreach ($item->deviations as $deviation) {
                            $this->scanScheduler->scheduleScan("DeviantArt://deviation/{$deviation->deviationid}");
                            $total++;
                        }
                    }
                }
                $this->notifications->log("scanned watchlist, total so far is {$total}");
                if ($iteration % 4 === 0) {
                    sleep(4);
                }
            } while ($response->has_more);
        } catch (ApiException $e) {
            if ($e->getCode() == 403) {
                sleep(10);
                $this->notifications->log($e->getMessage());
                $this->scanWatchlist($cursor, $total);
                $silentEnd = true;
            } else {
                throw $e;
            }
        }
        if (!$silentEnd) {
            $this->notifications->alert("Watchlist contained a total of {$total} submissions, scans scheduled");
        }
    }
}
