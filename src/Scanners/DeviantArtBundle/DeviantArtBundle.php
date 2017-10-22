<?php

namespace DownloadApp\Scanners\DeviantArtBundle;

use DownloadApp\Scanners\DeviantArtBundle\DependencyInjection\DeviantartExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class DeviantArtBundle
 * @package DownloadApp\Scanners\DeviantArtBundle
 */
class DeviantArtBundle extends Bundle
{
    /**
     * Returns the bundle's container extension.
     *
     * @return ExtensionInterface|null The container extension
     *
     * @throws \LogicException
     */
    public function getContainerExtension()
    {
        return new DeviantartExtension();
    }

}
