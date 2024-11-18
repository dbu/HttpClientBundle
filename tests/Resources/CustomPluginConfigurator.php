<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Resources;

use Http\HttplugBundle\PluginConfigurator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class CustomPluginConfigurator implements PluginConfigurator
{
    public static function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('custom_plugin');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('name')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $treeBuilder;
    }

    public function create(array $config): CustomPlugin
    {
        return new CustomPlugin($config['name']);
    }
}
