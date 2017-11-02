<?php

namespace DownloadApp\App\DownloadBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DownloadCommand
 * @package DownloadApp\App\DownloadBundle\Command
 */
class DownloadCommand extends ContainerAwareCommand
{
    const QUEUE = 'download';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('download:start')
            ->setDescription('Start a download')
            ->addArgument('key', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $downloadService = $this
            ->getContainer()
            ->get('downloadapp.download');
        $download = $downloadService->findByGUID($input->getArgument('key'));
        $downloadService->download($download);
    }
}
