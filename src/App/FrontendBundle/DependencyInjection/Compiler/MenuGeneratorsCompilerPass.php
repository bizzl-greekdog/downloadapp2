<?php

namespace DownloadApp\App\FrontendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MenuGeneratorsCompilerPass
 *
 * @package DownloadApp\App\FrontendBundle\DependencyInjection\Compiler
 */
class MenuGeneratorsCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $menuBuilders = $container->findTaggedServiceIds('downloadapp.menus.builder');
        $menuGenerators = $container->findTaggedServiceIds('downloadapp.menus.generator');

        foreach ($menuBuilders as $id => $tag) {
            $menuBuilderDefinition = $container->findDefinition($id);
            $localGenerators = array_filter(
                $menuGenerators, function ($tag) use ($id) {
                return $tag[0]['menu'] == $id;
            }
            );
            foreach ($localGenerators as $generatorId => $generatorTag) {
                $menuBuilderDefinition->addMethodCall(
                    'addGenerator',
                    [
                        $container->getDefinition($generatorId),
                        $generatorTag[0]['priority'],
                    ]
                );
            }
        }
    }
}
