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
use DownloadApp\App\DownloadBundle\Service\Downloads;
use DownloadApp\App\UserBundle\Service\CurrentUser;
use DownloadApp\App\UtilsBundle\Service\Notifications;
use DownloadApp\App\UtilsBundle\Service\PathUtils;
use DownloadApp\Scanners\CoreBundle\Service\ScanScheduler;
use DownloadApp\Scanners\FurAffinityBundle\Exception\NotAFurAffinityPageException;
use GuzzleHttp\Client;
use League\Uri\Uri;
use PHPHtmlParser\Dom;

/**
 * Class Scanner
 * @package DownloadApp\Scanners\FurAffinityBundle\Service
 */
class Scanner
{
    const QUEUE = 'scanners.furaffinity';

    /** @var  Client */
    private $client;

    /** @var  EntityManager */
    private $entityManager;

    /** @var  CurrentUser */
    private $currentUser;

    /** @var  Downloads */
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
     * @param Downloads $downloader
     * @param PathUtils $pathUtils
     * @param ScanScheduler $scanScheduler
     * @param Notifications $notifications
     */
    public function __construct(Client $client, EntityManager $entityManager, CurrentUser $currentUser, Downloads $downloader, PathUtils $pathUtils, ScanScheduler $scanScheduler, Notifications $notifications)
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
        $guid = implode(
            ':', [
                   'furaffinity',
                   'submission',
                   $this->currentUser->get()->getUsernameCanonical(),
                   $path[1],
               ]
        );

        if ($this->downloader->findByGUID($guid)) {
            return;
        }

        $response = $this->client->get($url);

        $dom = new Dom();
        $dom->load($response->getBody()->getContents());

        $fileUrl = $uri->getScheme() . '://' . preg_replace('#^//#', '', $dom->find('a[href*=facdn]', 0)->getAttribute('href'));
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
            ->setUser($this->currentUser->get());

        $this
            ->downloader
            ->persist($download)
            ->schedule($download);
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
            $response = $this->client->get($this->pathUtils->join($url, $i++));
            $dom->load($response->getBody()->getContents());
            $submissions = $dom->find('.submission-list a[href*="/view/"]');
            /** @var Dom\AbstractNode $submission */
            foreach ($submissions as $submission) {
                $submissionsUrls[] = $this->pathUtils->join('http://www.furaffinity.net/', $submission->getAttribute('href'));
            }
            $this->notifications->log("page {$i} of {$url} scanned");
        } while (count($submissions));
        $submissionsUrls = array_unique($submissionsUrls);
        array_map([$this->scanScheduler, 'scheduleScan'], $submissionsUrls);
        $total = count($submissionsUrls);
        $this->notifications->alert("Gallery contained a total of {$total} submissions, scans scheduled");
    }

    /**
     * Scan a users watchlist.
     */
    public function fetchWatchlist()
    {
        $url = 'http://www.furaffinity.net/msg/submissions/';
        $dom = new Dom();
        $submissionsUrls = [];
        do {
            $added = 0;
            $response = $this->client->get($url);
            $dom->load($response->getBody()->getContents());
            $submissions = $dom->find('#messages-form .t-image a');
            /** @var Dom\AbstractNode $submission */
            foreach ($submissions as $submission) {
                $submissionsUrl = $submission->getAttribute('href');
                if (substr($submissionsUrl, 0, 6) == '/view/') {
                    $submissionsUrl = $this->pathUtils->join('http://www.furaffinity.net/', $submissionsUrl);
                    if (!isset($submissionsUrls[$submissionsUrl])) {
                        $submissionsUrls[$submissionsUrl] = true;
                        $added++;
                    }
                }
            }
            if ($added) {
                $this->notifications->log("Another {$added} submissions found in watchlist, proceed to next page");
                $nextButtons = $dom->find('a.more, a.more-half')->toArray();
                $nextButtons = array_filter($nextButtons, function (Dom\AbstractNode $node) {
                    return strpos($node->getAttribute('class'), 'prev') === false;
                });
                if ($nextButtons) {
                    $url = $this->pathUtils->join('http://www.furaffinity.net', $nextButtons[0]->getAttribute('href'));
                } else {
                    break;
                }
            }
        } while ($added);
        $submissionsUrls = array_keys($submissionsUrls);
        array_map([$this->scanScheduler, 'scheduleScan'], $submissionsUrls);
        $total = count($submissionsUrls);
        $this->notifications->alert("Your FurAffinity watchlist contained a total of {$total} submissions, scans scheduled");
    }

    /**
     * Scan a furaffinity page.
     *
     * @param string $url
     * @throws NotAFurAffinityPageException
     */
    public function scan(string $url)
    {
        $uri = Uri::createFromString($url);
        $path = $this->pathUtils->split($uri->getPath());
        switch (strtolower($path[0])) {
            case 'gallery':
            case 'scraps':
            case 'favorites':
                $this->scanGallery($url);
                break;
            case 'view':
            case 'full':
                $this->scanSubmission($url);
                break;
            case 'user':
                $this->scanScheduler->scheduleScan("http://www.furaffinity.net/gallery/{$path[1]}/");
                $this->scanScheduler->scheduleScan("http://www.furaffinity.net/scraps/{$path[1]}/");
                break;
            default:
                throw new NotAFurAffinityPageException($url);
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
