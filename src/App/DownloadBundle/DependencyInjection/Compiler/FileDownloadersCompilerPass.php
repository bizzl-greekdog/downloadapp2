<?php

namespace DownloadApp\App\DownloadBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class FileDownloadersCompilerPass
 * @package Benkle\DownloadApp\DownloadBundle\DependencyInjection\Compiler
 */
class FileDownloadersCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $downloaderDefinition = $container->findDefinition('downloadapp.download');

        $fileDownloaders = $container->findTaggedServiceIds('downloadapp.file.downloader');

        foreach ($fileDownloaders as $id => $fileDownloader) {
            $downloaderDefinition->addMethodCall(
                'setFileDownloader',
                [
                    $fileDownloader[0]['for'],
                    $container->getDefinition($id),
                ]
            );
        }
    }
}
