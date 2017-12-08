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


namespace DownloadApp\Scanners\CoreBundle\Service;


use Doctrine\ORM\EntityManager;
use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\RemoteFile;
use DownloadApp\App\DownloadBundle\Service\Downloads;
use DownloadApp\App\UserBundle\Service\CurrentUser;
use League\Uri\Uri;

/**
 * Class Scanner
 * @package DownloadApp\Scanners\CoreBundle\Service
 */
class Scanner
{
    /** @var  CurrentUser */
    private $currentUser;

    /** @var Downloads */
    private $downloads;

    /**
     * Scanner constructor.
     *
     * @param CurrentUser $currentUser
     * @param EntityManager $em
     * @param Downloads $downloads
     */
    public function __construct(CurrentUser $currentUser, Downloads $downloads)
    {
        $this->currentUser = $currentUser;
        $this->downloads = $downloads;
    }

    /**
     * "Scan" a general url into a download
     *
     * @param string $url
     * @param string $referer
     * @throws \DownloadApp\App\DownloadBundle\Exceptions\DownloadAlreadyExistsException
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function scan(string $url, string $referer)
    {
        $uri = Uri::createFromString($url);

        $file = new RemoteFile();
        $file
            ->setReferer($referer)
            ->setUrl($url)
            ->setFilename(basename($uri->getPath()));

        $download = new Download(md5("generic:{$url}@{$referer}"));
        $download
            ->setUser($this->currentUser->get())
            ->setMetadatum('Found at', $referer ?? '')
            ->setComment('')
            ->setFile($file);

        $this
            ->downloads
            ->persist($download)
            ->schedule($download);
    }
}
