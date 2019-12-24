<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use ReflectionClass;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function constant;
use function count;
use function in_array;
use function is_string;
use function method_exists;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;

/**
 * DoctrineMigrationsExtension configuration structure.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder The config tree builder
     */
    public function getConfigTreeBuilder() : TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_migrations');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('doctrine_migrations', 'array');
        }

        $organizeMigrationModes = $this->getOrganizeMigrationsModes();

        $rootNode
            ->fixXmlConfig('migration', 'migrations')
            ->fixXmlConfig('migrations_path', 'migrations_paths')
            ->children()
                ->scalarNode('name')->defaultValue('Application Migrations')->end()

                ->arrayNode('migrations_paths')

                    ->info('A list of pairs namespace/path where to look for migrations.')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('namespace')
                    ->defaultValue(['%kernel.project_dir%/src/Migrations' => 'App\Migrations'])
                    ->prototype('scalar')->end()
                    ->validate()
                        ->ifTrue(static function ($v) {
                            return count($v) === 0;
                        })
                        ->thenInvalid('At least one migrations path must be specified.')
                    ->end()
                 ->end()

                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->info('Storage to use for migration status metadata.')
                    ->children()
                        ->scalarNode('id')
                            ->info('Custom metadata storage service ID.')
                            ->defaultValue(null)
                        ->end()
                        ->arrayNode('table_storage')
                            ->addDefaultsIfNotSet()
                            ->info('The default metadata storage, implemented as table in the database.')
                            ->children()
                                ->scalarNode('table_name')->defaultValue(null)->cannotBeEmpty()->end()
                                ->scalarNode('version_column_name')->defaultValue(null)->end()
                                ->scalarNode('version_column_length')->defaultValue(null)->end()
                                ->scalarNode('executed_at_column_name')->defaultValue(null)->end()
                                ->scalarNode('execution_time_column_name')->defaultValue(null)->end()
                            ->end()
                        ->end()
                    ->end()

                ->end()
                ->arrayNode('migrations')
                    ->info('A list of migrations to load in addition the the one discovered via "migrations_paths".')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->scalarNode('sorter')
                    ->info('Alternative migrations sorting algorithm')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('connection')
                    ->info('Connection name to use for the migrations database.')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('em')
                    ->info('Entity manager name to use for the migrations database (available when doctrine/orm is installed).')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('all_or_nothing')
                    ->info('Run all migrations in a transaction.')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('check_database_platform')
                    ->info('Adds an extra check in the generated migrations to ensure that is executed on the same database type.')
                    ->defaultValue(true)
                ->end()
                ->scalarNode('custom_template')
                    ->info('Custom template path for generated migration classes.')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('organize_migrations')
                    ->defaultValue(false)
                    ->info('Organize migrations mode. Possible values are: "BY_YEAR", "BY_YEAR_AND_MONTH", false')
                    ->validate()
                        ->ifTrue(static function ($v) use ($organizeMigrationModes) {
                            if ($v === false) {
                                return false;
                            }

                            if (is_string($v) && in_array(strtoupper($v), $organizeMigrationModes, true)) {
                                return false;
                            }

                            return true;
                        })
                        ->thenInvalid('Invalid organize migrations mode value %s')
                    ->end()
                    ->validate()
                        ->ifString()
                            ->then(static function ($v) {
                                return constant('Doctrine\Migrations\Configuration\Configuration::VERSIONS_ORGANIZATION_' . strtoupper($v));
                            })
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }


    /**
     * Find organize migrations modes for their names
     *
     * @return string[]
     */
    private function getOrganizeMigrationsModes() : array
    {
        $constPrefix = 'VERSIONS_ORGANIZATION_';
        $prefixLen   = strlen($constPrefix);
        $refClass    = new ReflectionClass('Doctrine\Migrations\Configuration\Configuration');
        $constsArray = $refClass->getConstants();
        $namesArray  = [];

        foreach ($constsArray as $key => $value) {
            if (strpos($key, $constPrefix) !== 0) {
                continue;
            }

            $namesArray[] = substr($key, $prefixLen);
        }

        return $namesArray;
    }
}
