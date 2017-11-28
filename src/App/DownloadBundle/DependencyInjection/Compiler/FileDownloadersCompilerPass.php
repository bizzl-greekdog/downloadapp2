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
        $downloadServiceDefinition = $container->findDefinition('downloadapp.download');

        $fileDownloadServices = $container->findTaggedServiceIds('downloadapp.file.downloader');

        foreach ($fileDownloadServices as $id => $fileDownloadService) {
            $downloadServiceDefinition->addMethodCall(
                'setFileDownloader',
                [
                    $fileDownloadService[0]['for'],
                    $container->getDefinition($id),
                ]
            );
        }
    }
}
