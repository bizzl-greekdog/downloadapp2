<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WatchlistCommand
 * @package DownloadApp\Scanners\DeviantArtBundle\Command
 */
class WatchlistCommand extends ContainerAwareCommand
{
    const NAME = 'deviantart:watchlist';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Fetch a users watchlist')
            ->addArgument('user', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fetchingService = $this
            ->getContainer()
            ->get('downloadapp.scanners.deviantart.scanner');
        $currentUserService = $this
            ->getContainer()
            ->get('downloadapp.user.current');
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $currentUserService->set($user);
        $fetchingService->fetchWatchlist();
        sleep(5); // Cooldown
    }
}
