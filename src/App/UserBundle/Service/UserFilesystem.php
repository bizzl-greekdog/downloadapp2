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
use DownloadApp\App\UtilsBundle\Service\PathUtils;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

/**
 * Class UserFilesystem
 * @package DownloadApp\App\UserBundle\Service
 */
class UserFilesystem
{
    /** @var  CurrentUser */
    private $currentUser;

    /** @var  string */
    private $baseDir;

    /** @var  PathUtils */
    private $pathUtils;

    /**
     * UserFilesystem constructor.
     * @param CurrentUser $currentUser
     * @param PathUtils $pathUtils
     * @param string $baseDir
     */
    public function __construct(
        CurrentUser $currentUser,
        PathUtils $pathUtils,
        string $baseDir
    )
    {
        $this->currentUser = $currentUser;
        $this->pathUtils = $pathUtils;
        $this->baseDir = $baseDir;
    }

    /**
     * Get a filesystem that we can download the users files to.
     *
     * @param User|null $user
     * @return FilesystemInterface
     */
    public function get(User $user = null): FilesystemInterface
    {
        $user = $user ?? $this->currentUser->get();
        $path = $this->pathUtils->join($this->baseDir, $user->getUsernameCanonical());
        return new Filesystem(new Local($path));
    }
}
