<?php

namespace DownloadApp\App\FrontendBundle;

use DownloadApp\App\FrontendBundle\DependencyInjection\Compiler\MenuGeneratorsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FrontendBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new MenuGeneratorsCompilerPass());
    }
}
