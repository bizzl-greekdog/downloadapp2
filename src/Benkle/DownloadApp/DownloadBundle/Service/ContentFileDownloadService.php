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


namespace Benkle\DownloadApp\DownloadBundle\Service;

use Benkle\DownloadApp\DownloadBundle\Entity\ContentFile;
use Benkle\DownloadApp\DownloadBundle\Entity\File;
use League\Flysystem\FilesystemInterface;

/**
 * Class ContentFileDownloadService
 *
 * This service "downloads" content files.
 *
 * @package Benkle\DownloadApp\DownloadBundle\Service
 */
class ContentFileDownloadService implements FileDownloadServiceInterface
{
    use SafeFilenameTrait;

    /**
     * Download a file entity.
     *
     * @param File $file
     * @param FilesystemInterface $fs
     * @return string The final filename
     */
    public function download(File $file, FilesystemInterface $fs): string
    {
        /** @var ContentFile $file */
        $filename = $this->findSafeFilename($file->getFilename(), $fs);
        $fs->put($filename, $file->getContent());
        return $filename;
    }
}
