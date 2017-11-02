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


use DownloadApp\App\UserBundle\Exception\NoLoggedInUserException;
use FOS\UserBundle\Model\User as AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class CurrentUserService
 * @package DownloadApp\App\UserBundle\Service
 */
class CurrentUserService
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AbstractUser
     */
    private $user;

    /**
     * CurrentUserService constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Get the user, from either preset or token.
     *
     * @return AbstractUser
     * @throws NoLoggedInUserException
     */
    public function getUser(): AbstractUser
    {
        if (isset($this->user)) {
            return $this->user;
        } else {
            $token = $this->tokenStorage->getToken();
            $user = $token->getUser();
            if (!isset($user)) {
                throw new NoLoggedInUserException();
            }
            return $user;
        }
    }

    /**
     * Preset a user to use instead of the token storage.
     *
     * @param AbstractUser $user
     * @return $this
     */
    public function setUser(AbstractUser $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Clear the preset user.
     *
     * @return $this
     */
    public function clearUser()
    {
        $this->user = null;
        return $this;
    }
}
