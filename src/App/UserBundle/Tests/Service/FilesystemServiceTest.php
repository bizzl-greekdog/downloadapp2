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
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;

// @codeCoverageIgnoreStart
class FilesystemServiceTest extends TestCase
{
    public function testGetFilesystemForLoggedInUser()
    {
        $userMock = $this->createMock(User::class);
        $userMock
            ->method('getUsernameCanonical')
            ->willReturn('User');
        $currentUserServiceMock = $this->createMock(CurrentUserService::class);
        $currentUserServiceMock
            ->method('getUser')
            ->willReturn($userMock);

        $service = new FilesystemService($currentUserServiceMock, new PathUtilsService(), 'Root');

        $this->assertInstanceOf(FilesystemInterface::class, $service->getUserFilesystem());
    }
}
// @codeCoverageIgnoreEnd
