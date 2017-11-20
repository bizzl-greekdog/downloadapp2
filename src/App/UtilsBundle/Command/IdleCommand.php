<?php

namespace DownloadApp\App\UtilsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class IdleCommand
 * @package DownloadApp\App\UtilsBundle\Command
 */
class IdleCommand extends ContainerAwareCommand
{
    const NAME = 'utils:idle';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Idles for a number of seconds')
            ->addArgument('seconds', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $seconds = intval($input->getArgument('seconds'), 10);
        sleep($seconds);
    }
}
