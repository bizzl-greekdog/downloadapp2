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
use DownloadApp\App\DownloadBundle\Command\DownloadCommand;
use DownloadApp\App\DownloadBundle\Entity\Download;
use DownloadApp\App\DownloadBundle\Entity\RemoteFile;
use DownloadApp\App\UserBundle\Service\CurrentUser;
use JMS\JobQueueBundle\Entity\Job;
use League\Uri\Uri;

/**
 * Class Scanner
 * @package DownloadApp\Scanners\CoreBundle\Service
 */
class Scanner
{
    /** @var  CurrentUser */
    private $currentUser;

    /** @var  EntityManager */
    private $em;

    /**
     * Scanner constructor.
     *
     * @param CurrentUser $currentUser
     * @param EntityManager $em
     */
    public function __construct(CurrentUser $currentUser, EntityManager $em)
    {
        $this->currentUser = $currentUser;
        $this->em = $em;
    }

    /**
     * "Scan" a general url into a download
     *
     * @param string $url
     * @param string $referer
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
        $this->em->persist($download);

        $job = new Job(DownloadCommand::NAME, [$download->getGuid()], DownloadCommand::QUEUE);
        $this->em->persist($job);

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
        $this->em->flush();
    }
}
