<?php

namespace DownloadApp\Scanners\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ScanCommand
 * @package DownloadApp\Scanners\CoreBundle\Command
 */
class ScanCommand extends ContainerAwareCommand
{
    const NAME = 'scanner:generic';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Start a generic download scan')
            ->addArgument('user', InputArgument::REQUIRED)
            ->addArgument('url', InputArgument::REQUIRED)
            ->addArgument('referer', InputArgument::OPTIONAL);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $genericScanner = $this
            ->getContainer()
            ->get('downloadapp.scanners.generic.scanner');
        $currentUser = $this
            ->getContainer()
            ->get('downloadapp.user.current');
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $currentUser->set($user);
        $genericScanner->scan($input->getArgument('url'), $input->getArgument('referer'));
    }
}
