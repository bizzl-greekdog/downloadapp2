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


namespace DownloadApp\App\DownloadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class RemoteFile
 *
 * Remote files can be downloaded via guzzle.
 *
 * @package Benkle\DownloadApp\DownloadBundle\Entity
 * @ORM\Entity()
 * @ORM\Table(name="download_files_remote")
 */
class RemoteFile extends File
{
    /**
     * @var string
     * @ORM\Column(type="string", length=1024)
     * @Serializer\Expose()
     */
    private $url;

    /**
     * @var string
     * @ORM\Column(type="string", length=1024)
     * @Serializer\Expose()
     */
    private $referer;

    /**
     * Get the referer.
     *
     * @return string
     */
    public function getReferer(): string
    {
        return $this->referer;
    }

    /**
     * Set the referer.
     *
     * @param string $referer
     * @return RemoteFile
     */
    public function setReferer(string $referer): RemoteFile
    {
        $this->referer = $referer;
        return $this;
    }

    /**
     * Get the url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the url.
     *
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): RemoteFile
    {
        $this->url = $url;
        return $this;
    }
}
