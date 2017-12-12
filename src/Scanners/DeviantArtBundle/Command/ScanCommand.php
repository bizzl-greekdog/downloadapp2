<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Command;

use Benkle\Deviantart\Exceptions\ApiException;
use DownloadApp\App\DownloadBundle\Exceptions\DownloadAlreadyExistsException;
use DownloadApp\Scanners\DeviantArtBundle\Service\Scanner;
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
    const NAME = 'scanner:deviantart';

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
        $scanner = $this
            ->getContainer()
            ->get('downloadapp.scanners.deviantart.scanner');
        $currentUser->set($user);
        $url = $input->getArgument('url');
        try {
            $scanner->scan($url);
        } catch (ApiException $e) {
            if (in_array($e->getCode(), [403, 429]) && $input->hasOption('jms-job-id')) {
                $thisJob = $jobs->find($input->getOption('jms-job-id'));
                $jobs->reschedule($thisJob, '+1 minutes', true);
                $jobs->pauseQueue(Scanner::QUEUE, 60, true);
                $output->writeln($e->__toString());
            } else {
                throw $e;
            }
        } catch (DownloadAlreadyExistsException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }
        sleep(5); // Cooldown
    }
}
