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


namespace DownloadApp\App\UtilsBundle\Listener;


use Benkle\NotificationBundle\Event\NotificationEvent;
use Benkle\NotificationBundle\Listener\AbstractNotificationListener;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NotificationToOutputListener
 * @package DownloadApp\App\UtilsBundle\Listener
 */
class NotificationToOutputListener extends AbstractNotificationListener
{
    /** @var  OutputInterface */
    private $output;

    /**
     * NotificationToOutputListener constructor.
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Handle a notification event.
     *
     * @param NotificationEvent $event
     * @return mixed
     */
    public function handleNotificationEvent(NotificationEvent $event)
    {
        switch ($event->getPriority()) {
            case NotificationEvent::PRIORITY_VERY_HIGH:
            case NotificationEvent::PRIORITY_HIGH:
                $this->output->writeln(sprintf('<info>%s</info>', $event->getMessage()));
                break;
            case NotificationEvent::PRIORITY_LOW:
            case NotificationEvent::PRIORITY_VERY_LOW:
            $this->output->writeln(sprintf('<comment>%s</comment>', $event->getMessage()));
                break;
            default:
                $this->output->writeln($event->getMessage());
        }
    }
}
