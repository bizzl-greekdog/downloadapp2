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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

// @codeCoverageIgnoreStart
class CurrentUserServiceTest extends TestCase
{
    /**
     * @expectedException \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function testNotLoggedIn()
    {
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock
            ->method('getUser')
            ->willReturn(null);
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenStorageMock
            ->method('getToken')
            ->willReturn($tokenMock);
        $service = new CurrentUser($tokenStorageMock);

        $service->get();
    }

    public function testLoggedIn()
    {
        $userMock = $this->createMock(User::class);
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock
            ->method('getUser')
            ->willReturn($userMock);
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenStorageMock
            ->method('getToken')
            ->willReturn($tokenMock);
        $service = new CurrentUser($tokenStorageMock);

        $this->assertEquals($userMock, $service->get());
    }

    public function testUserOverride()
    {
        $userMock = $this->createMock(User::class);
        $userMock2 = $this->createMock(User::class);
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock
            ->method('getUser')
            ->willReturn($userMock);
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenStorageMock
            ->method('getToken')
            ->willReturn($tokenMock);
        $service = new CurrentUser($tokenStorageMock);

        $this->assertEquals($service, $service->set($userMock2));
        $this->assertEquals($userMock2, $service->get());
    }

    public function testUserOverrideClearing()
    {
        $userMock = $this->createMock(User::class);
        $userMock2 = $this->createMock(User::class);
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock
            ->method('getUser')
            ->willReturn($userMock);
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenStorageMock
            ->method('getToken')
            ->willReturn($tokenMock);
        $service = new CurrentUser($tokenStorageMock);

        $this->assertEquals($service, $service->set($userMock2));
        $this->assertEquals($service, $service->clear());
        $this->assertEquals($userMock, $service->get());
    }
}
// @codeCoverageIgnoreEnd
