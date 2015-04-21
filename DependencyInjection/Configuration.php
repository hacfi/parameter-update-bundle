<?php

/*
 * (c) Philipp Wahala <philipp.wahala@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace hacfi\ParameterUpdateBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    private $alias;

    /**
     * Constructor.
     *
     * @param string $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder
            ->root($this->alias)
            ->children()
                ->scalarNode('parameters_file')
                    ->defaultValue('%kernel.root_dir%/config/parameters.yml')
                ->end()
                ->scalarNode('parameters_key')
                    ->defaultValue('parameters')
                ->end()
                ->arrayNode('values')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->variableNode('service')
                            ->end()
                            ->scalarNode('parameters_file')
                            ->end()
                            ->scalarNode('parameters_key')
                            ->end()
                            ->scalarNode('property_path')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
