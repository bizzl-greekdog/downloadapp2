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
use League\Flysystem\FilesystemInterface;

/**
 * Trait SafeFilenameTrait
 *
 * We don't want to overwrite an existing file, so we use this trait to produce safe filenames.
 *
 * @package Benkle\DownloadApp\DownloadBundle\Service
 */
trait SafeFilenameTrait
{
    /**
     * Extend a filename, so it won't clash with an existing file.
     *
     * @param string $filename
     * @param FilesystemInterface $fs
     * @return string
     */
    protected function findSafeFilename(string $filename, FilesystemInterface $fs): string
    {
        $i = 1;
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        while ($fs->has($filename)) {
            $filename = implode('.', [$basename, $i++, $extension]);
        }
        return $filename;
    }
}
