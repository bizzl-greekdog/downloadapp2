<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Command;

use Benkle\Deviantart\Exceptions\ApiException;
use Benkle\Deviantart\Exceptions\UnauthorizedException;
use JMS\JobQueueBundle\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ScanCommand
 * @package DownloadApp\Scanners\DeviantArtBundle\Command
 */
class ScanCommand extends ContainerAwareCommand
{
    const NAME = 'deviantart:scan';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Scan a deviantart url')
            ->addArgument('user', InputArgument::REQUIRED)
            ->addArgument('url', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fetchingService = $this
            ->getContainer()
            ->get('downloadapp.scanners.deviantart.fetching');
        $currentUserService = $this
            ->getContainer()
            ->get('downloadapp.user.current');
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $currentUserService->setUser($user);
        $url = $input->getArgument('url');
        try {
            $fetchingService->fetchFromAppUrl($url);
        } catch (ApiException $e) {
            sleep(60);
            throw $e;
        }
        sleep(5); // Cooldown
    }
}
