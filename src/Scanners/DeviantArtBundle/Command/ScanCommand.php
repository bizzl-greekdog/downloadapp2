<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Command;

use Benkle\Deviantart\Exceptions\UnauthorizedException;
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
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('deviantart:deviation:scan')
            ->setDescription('Scan a deviation')
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
        $url = $input->getArgument('url');
        try {
            $appUrl = $fetchingService->getAppUrl($url);
            $parts = explode('/', $appUrl);
            $fetchingService->getDownloadForDeviation(end($parts));
        } catch (UnauthorizedException $e) {
            $output->writeln($e->__toString());
        }
    }
}
