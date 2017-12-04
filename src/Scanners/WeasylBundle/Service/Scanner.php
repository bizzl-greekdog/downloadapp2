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


namespace DownloadApp\Scanners\WeasylBundle\Service;

use Doctrine\ORM\EntityManager;
use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\RemoteFile;
use DownloadApp\App\DownloadBundle\Exceptions\DownloadAlreadyExistsException;
use DownloadApp\App\DownloadBundle\Service\Downloader;
use DownloadApp\App\UserBundle\Service\CurrentUser;
use DownloadApp\App\UtilsBundle\Service\Notifications;
use DownloadApp\App\UtilsBundle\Service\PathUtils;
use DownloadApp\Scanners\CoreBundle\Service\ScanScheduler;
use DownloadApp\Scanners\WeasylBundle\Exception\NotAWeasylPageException;
use GuzzleHttp\Client;
use League\Uri\Uri;
use PHPHtmlParser\Dom;


/**
 * Class Scanner
 * @package DownloadApp\Scanners\WeasylBundle\Service
 */
class Scanner
{
    const QUEUE = 'scanners.weasyl';

    /** @var  Client */
    private $client;

    /** @var  EntityManager */
    private $entityManager;

    /** @var  CurrentUser */
    private $currentUser;

    /** @var  Downloader */
    private $downloader;

    /** @var  PathUtils */
    private $pathUtils;

    /** @var  ScanScheduler */
    private $scanScheduler;

    /** @var  Notifications */
    private $notifications;

    /**
     * Scanner constructor.
     * @param Client $client
     * @param EntityManager $entityManager
     * @param CurrentUser $currentUser
     * @param Downloader $downloader
     * @param PathUtils $pathUtils
     * @param ScanScheduler $scanScheduler
     * @param Notifications $notifications
     */
    public function __construct(Client $client, EntityManager $entityManager, CurrentUser $currentUser, Downloader $downloader, PathUtils $pathUtils, ScanScheduler $scanScheduler, Notifications $notifications)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->currentUser = $currentUser;
        $this->downloader = $downloader;
        $this->pathUtils = $pathUtils;
        $this->scanScheduler = $scanScheduler;
        $this->notifications = $notifications;
    }

    /**
     * Scan a single submission.
     *
     * @param string $url
     * @throws DownloadAlreadyExistsException
     */
    public function scanSubmission(string $url)
    {
        $uri = Uri::createFromString($url);
        $path = $this->pathUtils->split($uri->getPath());
        while ($path[0] != 'submissions') {
            array_shift($path);
        }
        $fileNr = $path[1];
        $guid = implode(
            ':', [
                   'weasyl',
                   'submission',
                   $this->currentUser->get()->getUsernameCanonical(),
                   $fileNr,
               ]
        );

        if ($this->downloader->findByGUID($guid)) {
            return;
        }

        $response = $this->client->get($url);

        $dom = new Dom();
        $dom->load($response->getBody()->getContents());

        $fileUrl = $uri->getScheme() . '//' . $dom->find('#detail-actions a[href*=submission], #detail-actions a[href*=submit]', 0)
                                                  ->getAttribute('href');
        $orgFilename = substr(basename($fileUrl), 0, -9);
        $title = $dom->find('#detail-bar-title', 0)->innerHtml;
        $artist = strip_tags($dom->find('#db-user .username', 0)->innerHtml);
        $description = $dom->find('#detail-description .formatted-content', 0)->innerHtml;

        $fileExt = pathinfo($orgFilename, PATHINFO_EXTENSION);
        $fileTitle = pathinfo($orgFilename, PATHINFO_FILENAME);
        $fileTitle = strtolower($fileTitle);
        $fileTitle = preg_replace("/['\"\n]/", '', $fileTitle);
        $fileTitle = preg_replace("/[ ]/", '_', $fileTitle);
        $filename = "weasyl_{$fileNr}_{$fileTitle}_by_{$artist}.{$fileExt}";

        $file = new RemoteFile();
        $file
            ->setFilename($orgFilename)
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
                    'Original Filename' => $orgFilename,
                ]
            )
            ->setFile($file)
            ->setUser($this->currentUser->get());

        $this->downloader->schedule($download);
        $this->notifications->log("{$title} by {$artist} scanned and download scheduled");
    }

    /**
     * Fetch a gallery, scraps or favourites.
     *
     * @param string $url
     */
    public function scanGallery(string $url)
    {
        $i = 1;
        $dom = new Dom();
        $submissionsUrls = [];
        do {
            $response = $this->client->get($url);
            $dom->load($response->getBody()->getContents());
            $submissions = $dom->find('.thumbnail-grid .item .thumb a.thumb-bounds');
            /** @var Dom\AbstractNode $submission */
            foreach ($submissions as $submission) {
                $submissionsUrls[] = $this->pathUtils->join('https://www.weasyl.com/', $submission->getAttribute('href'));
            }
            $this->notifications->log("page {$i} of {$url} scanned");
            $nextLink = $dom->find('.sectioned-main a.button[href*=nextid=]', 0);
            if ($nextLink) {
                $url = $this->pathUtils->join('https://www.weasyl.com/', $nextLink->getAttribute('href'));
            }
        } while ($nextLink);
        $submissionsUrls = array_unique($submissionsUrls);
        array_map([$this->scanScheduler, 'schedule'], $submissionsUrls);
        $total = count($submissionsUrls);
        $this->notifications->alert("Gallery contained a total of {$total} submissions, scans scheduled");
    }

    /**
     * Scan a users watchlist.
     */
    public function fetchWatchlist()
    {
        $url = 'https://www.weasyl.com/messages/submissions';
        $dom = new Dom();
        $submissionsUrls = [];
        do {
            $added = 0;
            $response = $this->client->get($url);
            $x = $response->getBody()->getContents();
            $dom->load($x);
            $submissions = $dom->find('.thumbnail-grid .item .thumb a.thumb-bounds');
            /** @var Dom\AbstractNode $submission */
            foreach ($submissions as $submission) {
                $submissionsUrl = $submission->getAttribute('href');
                $submissionsUrl = $this->pathUtils->join('https://www.weasyl.com/', $submissionsUrl);
                if (!isset($submissionsUrls[$submissionsUrl])) {
                    $submissionsUrls[$submissionsUrl] = true;
                    $added++;
                }
            }
            if ($added) {
                $this->notifications->log("Another {$added} submissions found in watchlist, proceed to next page");
                $nextButton = $dom->find('a.notifs-next', 0);
                if ($nextButton) {
                    $url = $this->pathUtils->join('https://www.weasyl.com/', $nextButton->getAttribute('href'));
                } else {
                    break;
                }
            }
        } while ($added);
        $submissionsUrls = array_keys($submissionsUrls);
        array_map([$this->scanScheduler, 'schedule'], $submissionsUrls);
        $total = count($submissionsUrls);
        $this->notifications->alert("Your Weasyl watchlist contained a total of {$total} submissions, scans scheduled");
    }

    public function scanFavorites(string $url)
    {}

    /**
     * Scan a weasyl page.
     *
     * @param string $url
     * @throws NotAWeasylPageException
     */
    public function scan(string $url)
    {
        $uri = Uri::createFromString($url);
        $path = $this->pathUtils->split($uri->getPath());

        if (substr($path[0], 0, 1) === '~') {
            $username = substr($path[0], 1);
            $path = array_slice($path, 1);
            if (empty($path)) {
                $path = ['user', $username];
            } elseif ($path[0] == 'submissions') {
                $path[0] = 'submission'; // Hack, because weasyl urls are weird
            }
        }

        switch (strtolower($path[0])) {
            case 'favorites':
                $this->scanFavorites($url);
                break;
            case 'submissions':
            case 'collections':
            case 'characters':
                $this->scanGallery($url);
                break;
            case 'character':
            case 'submission':
                $this->scanSubmission($url);
                break;
            case 'user':
                $this->scanScheduler->schedule("https://www.weasyl.com/submissions/{$path[1]}/");
                $this->scanScheduler->schedule("https://www.weasyl.com/characters/{$path[1]}/");
                break;
            default:
                throw new NotAWeasylPageException($url);
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
