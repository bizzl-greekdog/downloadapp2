<?php

namespace DownloadApp\Scanners\FurAffinityBundle\Command;

use DownloadApp\App\UtilsBundle\Listener\NotificationToOutputListener;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WatchlistCommand extends ContainerAwareCommand
{
    const NAME = 'furaffinity:watchlist';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Scan a users watchlist')
            ->addArgument('user', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scanner = $this
            ->getContainer()
            ->get('downloadapp.scanners.furaffinity.scanner');
        $eventDispatcher = $this
            ->getContainer()
            ->get('event_dispatcher');
        $eventDispatcher->addSubscriber(new NotificationToOutputListener($output));
        $currentUser = $this
            ->getContainer()
            ->get('downloadapp.user.current');
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $currentUser->set($user);
        $scanner->fetchWatchlist();
    }
}
