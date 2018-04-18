<?php

namespace DownloadApp\App\UtilsBundle\Command;

use DownloadApp\App\UtilsBundle\Listener\NotificationToOutputListener;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PushTestCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('utils:push:test')
            ->setDescription('Do a test push')
            ->addArgument('user', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $this->getContainer()->get('downloadapp.user.current')->set($user);
        $notifications = $this
            ->getContainer()
            ->get('downloadapp.utils.notifications');
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new NotificationToOutputListener($output));
        $notifications->log('Hello World');
        $notifications->alert('This is a test');
    }
}
