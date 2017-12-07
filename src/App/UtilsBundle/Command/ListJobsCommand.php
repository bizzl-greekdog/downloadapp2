<?php

namespace DownloadApp\App\UtilsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListJobsCommand extends ContainerAwareCommand
{
    const NAME = 'utils:jobs:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List Jobs for a user')
            ->addArgument('user', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentUser = $this
            ->getContainer()
            ->get('downloadapp.user.current');
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $jobs = $this
            ->getContainer()
            ->get('downloadapp.utils.jobs');
        $output->writeln($jobs->countJobsForUser($user));
        foreach ($jobs->findJobsForUser($user) as $job) {
            $output->writeln(sprintf(
                "%s\t%s\t%s\t%s",
                $job->getQueue(),
                $job->getState(),
                $job->getCommand(),
                implode(' ', $job->getArgs())
                             ));
        }
    }
}
