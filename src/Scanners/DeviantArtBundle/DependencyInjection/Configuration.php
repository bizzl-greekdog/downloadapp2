<?php

namespace DownloadApp\Scanners\DeviantArtBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deviantart');

        $rootNode
            ->children()
                ->arrayNode('authentication')
            ->children()
            ->scalarNode('id')->end()
            ->scalarNode('secret')->end()
            ->scalarNode('redirectUri')->end()
            ->end()
            ->end()
            ->arrayNode('simpleToken')
            ->children()
            ->scalarNode('filename')
            ->defaultValue('')
            ->end()
            ->end()
            ->end()
            ->arrayNode('userToken')
            ->children()
            ->scalarNode('directory')
            ->defaultValue('')
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
