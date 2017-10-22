<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AuthorizeCommand
 * @package DownloadApp\Scanners\DeviantArtBundle\Command
 */
class AuthorizeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('deviantart:authorize')
            ->setDescription('Manual authorize API access')
            ->addArgument('authCode');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $authCode = $input->getArgument('authCode');
        $api = $this->getContainer()->get('downloadapp.scanners.deviantart.api');
        $api->initialize($authCode);
    }
}
