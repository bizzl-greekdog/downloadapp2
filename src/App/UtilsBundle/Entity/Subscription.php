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


namespace DownloadApp\App\UtilsBundle\Entity;


use Benkle\NotificationBundle\Entity\SubscriptionInterface;
use Doctrine\ORM\Mapping as ORM;
use DownloadApp\App\UserBundle\Entity\User;

/**
 * Class Subscription
 * @package DownloadApp\App\UtilsBundle\Entity
 * @ORM\Entity()
 * @ORM\Table(name="subscriptions", uniqueConstraints={@ORM\UniqueConstraint(name="uniquness", columns={"endpoint", "key", "secret", "user_id"})})
 */
class Subscription implements SubscriptionInterface
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $endpoint;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $key;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $secret;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="DownloadApp\App\UserBundle\Entity\User", cascade={"persist", "remove"})
     */
    private $user;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser($user): Subscription
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get subscription endpoint.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Set subscription endpoint.
     *
     * @param string $endpoint
     * @return SubscriptionInterface
     */
    public function setEndpoint(string $endpoint): SubscriptionInterface
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Get subscription key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set subscription key.
     *
     * @param string $key
     * @return SubscriptionInterface
     */
    public function setKey(string $key): SubscriptionInterface
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Get subscription secret.
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Set subscription secret.
     *
     * @param string $secret
     * @return SubscriptionInterface
     */
    public function setSecret(string $secret): SubscriptionInterface
    {
        $this->secret = $secret;
        return $this;
    }
}
