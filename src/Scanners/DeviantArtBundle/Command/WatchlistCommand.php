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
    const NAME = 'watchlist:deviantart';

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
            ->get('downloadapp.scanners.deviantart.scanner');
        $currentUser = $this
            ->getContainer()
            ->get('downloadapp.user.current');
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $currentUser->set($user);
        $scanner->scanWatchlist();
        sleep(5); // Cooldown
    }
}
