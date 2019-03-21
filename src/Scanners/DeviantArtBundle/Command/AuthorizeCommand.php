<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

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
            ->addArgument('user', InputArgument::REQUIRED)
            ->addArgument('authCode', InputArgument::OPTIONAL);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $authCode = $input->getArgument('authCode');
        $user = $input->getArgument('user');
        $currentUser = $this
            ->getContainer()
            ->get('downloadapp.user.current');
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $currentUser->set($user);
        $api = $this->getContainer()->get('downloadapp.scanners.deviantart.api');
        try {
            $api->initialize($authCode);
        } catch (UnauthorizedException $e) {
            $output->writelin($e->getUrl());
        }
    }
}
