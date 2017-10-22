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


use Benkle\Deviantart\Exceptions\ApiException;
use Doctrine\ORM\EntityManager;
use DownloadApp\App\DownloadBundle\Entity\ContentFile;
use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\File;
use DownloadApp\App\DownloadBundle\Entity\RemoteFile;
use GuzzleHttp\Client;

/**
 * Class DeviationFetchingService
 * @package DownloadApp\Scanners\DeviantArtBundle\Service
 */
class DeviationFetchingService
{
    /** @var  ApiService */
    private $api;

    /** @var  Client */
    private $client;

    /** @var  EntityManager */
    private $entityManager;

    /**
     * DeviationFetchingService constructor.
     *
     * @param ApiService $api
     * @param Client $client
     */
    public function __construct(
        ApiService $api,
        Client $client,
        EntityManager $entityManager
    )
    {
        $this->api = $api;
        $this->client = $client;
        $this->entityManager = $entityManager;
    }

    /**
     * Extract an App URL from a website.
     *
     * @param string $url
     * @return string
     */
    public function getAppUrl(string $url): string
    {
        if (substr($url, 13) == 'DeviantArt://') {
            return $url;
        }
        $response = $this->client->get($url);
        $matches = [];
        preg_match(
            '/<meta.*?name="apple-itunes-app".*?>/',
            $response->getBody()->getContents(),
            $matches
        );
        preg_match(
            '/app-argument=.*?(?="|, )/',
            $matches[0],
            $matches
        );
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
     * @param string $deviationId
     */
    public function getDownloadForDeviation(string $deviationId)
    {
        $download = new Download("deviantart:deviation:$deviationId");
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
            ->metadata[0]
        ;

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
        ;

        $this->entityManager->persist($download);
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
