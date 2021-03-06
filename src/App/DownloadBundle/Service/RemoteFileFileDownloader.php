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


use DownloadApp\App\DownloadBundle\Entity\File;
use DownloadApp\App\DownloadBundle\Entity\RemoteFile;
use GuzzleHttp\Client;
use League\Flysystem\FilesystemInterface;

/**
 * Class RemoteFileFileDownloader
 *
 * Download a remote file.
 *
 * @package Benkle\DownloadApp\DownloadBundle\Service
 */
class RemoteFileFileDownloader implements FileDownloaderInterface
{
    use SafeFilenameTrait;

    /** @var Client */
    private $client;

    /**
     * RemoteFileFileDownloader constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Download a file entity.
     *
     * @param File $file
     * @param FilesystemInterface $fs
     * @return string The final filename
     */
    public function download(File $file, FilesystemInterface $fs): string
    {
        /** @var RemoteFile $file */
        $filename = $this->findSafeFilename($file->getFilename(), $fs);
        $response = $this->client->get($file->getUrl(), [
            'headers' => [
                'Referer' => $file->getReferer()
            ]
        ]);
        $fs->putStream($filename, $response->getBody()->detach());
        return $filename;
    }
}
