<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Command;

use Benkle\Deviantart\Exceptions\ApiException;
use DownloadApp\Scanners\DeviantArtBundle\Service\DeviantArtFetchingService;
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
        $currentUserService = $this
            ->getContainer()
            ->get('downloadapp.user.current');
        $user = $this
            ->getContainer()
            ->get('fos_user.user_provider.username')
            ->loadUserByUsername($input->getArgument('user'));
        $jobService = $this
            ->getContainer()
            ->get('downloadapp.utils.jobs');
        $currentUserService->setUser($user);
        $url = $input->getArgument('url');
        try {
            $fetchingService->fetchFromAppUrl($url);
        } catch (ApiException $e) {
            if (in_array($e->getCode(), [403, 429]) && $input->hasOption('jms-job-id')) {
                $thisJob = $jobService->find($input->getOption('jms-job-id'));
                $jobService->reschedule($thisJob, '+1 minutes', true);
                $jobService->pauseQueue(DeviantArtFetchingService::QUEUE, 60, true);
                $output->writeln($e->__toString());
            } else {
                throw $e;
            }
        }
        sleep(5); // Cooldown
    }
}
