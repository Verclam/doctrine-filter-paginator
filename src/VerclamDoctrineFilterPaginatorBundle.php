<?php

namespace Verclam\DoctrineFilterPaginator;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Verclam\DoctrineFilterPaginator\FilterPagerManager;

class VerclamDoctrineFilterPaginatorBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('timezone')
                    ->defaultValue('Europe/Paris')
                    ->info('The timezone used for date filtering (BETWEEN operator with date types)')
                ->end()
                ->scalarNode('total_records_key')
                    ->defaultValue('totalRecords')
                    ->info('The key name for the total records count in paginated results')
                ->end()
                ->scalarNode('results_key')
                    ->defaultValue('results')
                    ->info('The key name for the results in paginated results')
                ->end()
            ->end()
        ;
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container->import('../config/services.yaml');

        $container->services()
            ->get('verclam.doctrine_filter_paginator.date_utils')
            ->arg('$timezone', $config['timezone'])
        ;

        $container->services()
            ->get(FilterPagerManager::class)
            ->arg('$defaultTotalRecordsKey', $config['total_records_key'])
            ->arg('$defaultResultsKey', $config['results_key'])
        ;
    }
}
