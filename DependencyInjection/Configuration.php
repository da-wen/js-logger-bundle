<?php

namespace Dawen\Bundle\JsLoggerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $rootNode = $treeBuilder->root('js_logger');

        $this->process($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     * @author Daniel Wendlandt
     */
    private function process(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->scalarNode('enabled')
                    ->defaultValue(true)
                ->end()
                ->arrayNode('allowed_levels')
                    ->prototype('scalar')->end()
                ->end()

            ->end()
        ->end();
    }
}
