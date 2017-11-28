<?php

namespace DownloadApp\Scanners\CoreBundle;

use DownloadApp\Scanners\CoreBundle\DependencyInjection\Compiler\ContractorsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ContractorsCompilerPass());
    }
}
