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


namespace DownloadApp\App\UserBundle\Service;
use DownloadApp\App\UserBundle\Entity\User;
use DownloadApp\App\UtilsBundle\Service\PathUtilsService;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

/**
 * Class FilesystemService
 * @package DownloadApp\App\UserBundle\Service
 */
class FilesystemService
{
    /** @var  CurrentUserService */
    private $currentUserService;

    /** @var  string */
    private $baseDir;

    /** @var  PathUtilsService */
    private $pathUtilsService;

    /**
     * FilesystemService constructor.
     * @param CurrentUserService $currentUserService
     * @param PathUtilsService $pathUtilsService
     * @param string $baseDir
     */
    public function __construct(
        CurrentUserService $currentUserService,
        PathUtilsService $pathUtilsService,
        string $baseDir
    )
    {
        $this->currentUserService = $currentUserService;
        $this->pathUtilsService = $pathUtilsService;
        $this->baseDir = $baseDir;
    }

    /**
     * Get a filesystem that we can download the users files to.
     *
     * @param User|null $user
     * @return FilesystemInterface
     */
    public function getUserFilesystem(User $user = null): FilesystemInterface
    {
        $user = $user ?? $this->currentUserService->getUser();
        $path = $this->pathUtilsService->join($this->baseDir, $user->getUsernameCanonical());
        return new Filesystem(new Local($path));
    }
}
