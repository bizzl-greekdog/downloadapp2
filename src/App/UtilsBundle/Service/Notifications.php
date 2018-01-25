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


namespace DownloadApp\App\UtilsBundle\Service;

use Benkle\NotificationBundle\Event\NotificationEvent;
use DownloadApp\App\UserBundle\Service\CurrentUser;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Notifications
 * @package DownloadApp\Scanners\CoreBundle\Service
 */
class Notifications
{
    /** @var  CurrentUser */
    private $currentUser;

    /** @var  EventDispatcherInterface */
    private $dispatcher;

    /**
     * Notifications constructor.
     * @param CurrentUser $currentUser
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(CurrentUser $currentUser, EventDispatcherInterface $dispatcher)
    {
        $this->currentUser = $currentUser;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Send a notification.
     *
     * @param string $message
     * @param string $urgency
     * @return Event
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function send(string $message, string $urgency = NotificationEvent::URGENCY_NORMAL): Event
    {
        $notification = new NotificationEvent($this->currentUser->get());
        $notification->getPayload()
                     ->setBody($message)
                     ->setTitle('DownloadApp2');
        $notification->setUrgency($urgency);
        //$notification->setSendable(in_array($urgency, [NotificationEvent::URGENCY_HIGH, NotificationEvent::URGENCY_NORMAL]));
        return $notification->dispatchTo($this->dispatcher);
    }

    /**
     * Send a low priority notification.
     *
     * @param string $message
     * @return Event
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function log(string $message): Event
    {
        return $this->send($message, NotificationEvent::URGENCY_LOW);
    }

    /**
     * Send a high priority message.
     *
     * @param string $message
     * @return Event
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function alert(string $message): Event
    {
        return $this->send($message, NotificationEvent::URGENCY_HIGH);
    }
}
