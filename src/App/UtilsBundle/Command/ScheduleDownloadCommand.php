<?php
/*
 * Copyright (c) 2017 Benjamin Kleiner
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace DownloadApp\App\UtilsBundle\Command;

use DownloadApp\App\DownloadBundle\Entity\Download;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ScheduleDownloadCommand
 * @package DownloadApp\App\UtilsBundle\Command
 */
class ScheduleDownloadCommand extends ContainerAwareCommand
{
    const NAME = 'utils:downloads:schedule';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Schedule a download')
            ->addArgument('guid', InputArgument::OPTIONAL)
            ->addOption('all', 'a', InputOption::VALUE_NONE)
            ->addOption('force', 'f', InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasOption('all') && !$input->hasArgument('guid')) {
            $output->writeln('<error>Either download guid or --all are required</error>');
            return -1;
        }
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $downloads = $this->getContainer()->get('downloadapp.downloads');
        if ($input->hasOption('all')) {
            foreach ($em->getRepository(Download::class)->findBy(['downloaded' => false]) as $download) {
                if (!$downloads->isScheduled($download)) {
                    $downloads->schedule($download);
                }
            }
        } else {
            $guid = $input->getArgument('guid');
            $download = $downloads->findByGUID($guid);
            if ($input->hasOption('force') || !$downloads->isScheduled($download)) {
                $downloads->schedule($download);
            }
        }
        return 0;
    }
}
