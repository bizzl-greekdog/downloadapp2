<?php

namespace DownloadApp\App\DownloadBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class FileDownloadServiceCompilerPass
 * @package Benkle\DownloadApp\DownloadBundle\DependencyInjection\Compiler
 */
class FileDownloadServiceCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $downloadServiceDefinition = $container->findDefinition('downloadapp.download');

        $fileDownloadServices = $container->findTaggedServiceIds('downloadapp.file_download_service');

        foreach ($fileDownloadServices as $id => $fileDownloadService) {
            $downloadServiceDefinition->addMethodCall(
                'setFileDownloadService',
                [
                    $fileDownloadService[0]['for'],
                    $container->getDefinition($id),
                ]
            );
        }
    }
}
