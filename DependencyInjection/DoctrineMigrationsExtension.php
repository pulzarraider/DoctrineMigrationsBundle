<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use function sprintf;

/**
 * DoctrineMigrationsExtension.
 */
class DoctrineMigrationsExtension extends Extension
{
    /**
     * Responds to the migrations configuration parameter.
     *
     * @param string[][] $configs
     */
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $locator = new FileLocator(__DIR__ . '/../Resources/config/');
        $loader  = new XmlFileLoader($container, $locator);

        $loader->load('services.xml');

        $configurationDefinition = $container->getDefinition('doctrine.migrations.configuration');

        $configurationDefinition->addMethodCall('setName', [$config['name']]);

        foreach ($config['migrations_paths'] as $ns => $path) {
            $configurationDefinition->addMethodCall('addMigrationsDirectory', [$ns, $path]);
        }

        foreach ($config['migrations'] as $migrationClass) {
            $configurationDefinition->addMethodCall('addMigrationClass', [$migrationClass]);
        }

        if ($config['organize_migrations'] !== false) {
            $configurationDefinition->addMethodCall('setMigrationOrganization', [$config['organize_migrations']]);
        }

        if ($config['custom_template'] !== null) {
            $configurationDefinition->addMethodCall('setCustomTemplate', [$config['custom_template']]);
        }

        $configurationDefinition->addMethodCall('setAllOrNothing', [$config['all_or_nothing']]);
        $configurationDefinition->addMethodCall('setCheckDatabasePlatform', [$config['check_database_platform']]);

        $diDefinition = $container->getDefinition('doctrine.migrations.dependency_factory');

        if ($config['sorter'] !== null) {
            $diDefinition->addMethodCall('setService', [DependencyFactory::MIGRATIONS_SORTER, new Reference($config['sorter'])]);
        }

        if ($config['storage']['id'] !== null) {
            $diDefinition->addMethodCall('setService', [MetadataStorage::class, new Reference($config['storage']['id'])]);
        } else {
            $storageConfiguration = $config['storage']['table_storage'];

            $storageDefinition = new Definition(TableMetadataStorageConfiguration::class);
            $container->setDefinition('doctrine.migrations.storage.table_storage', $storageDefinition);
            $container->setAlias('doctrine.migrations.metadata_storage', 'doctrine.migrations.storage.table_storage');

            if ($storageConfiguration['table_name']!== null) {
                $storageDefinition->addMethodCall('setTableName', [$storageConfiguration['table_name']]);
            }
            if ($storageConfiguration['version_column_name']!== null) {
                $storageDefinition->addMethodCall('setVersionColumnName', [$storageConfiguration['version_column_name']]);
            }
            if ($storageConfiguration['version_column_length']!== null) {
                $storageDefinition->addMethodCall('setVersionColumnLength', [$storageConfiguration['version_column_length']]);
            }
            if ($storageConfiguration['executed_at_column_name']!== null) {
                $storageDefinition->addMethodCall('setExecutedAtColumnName', [$storageConfiguration['executed_at_column_name']]);
            }
            if ($storageConfiguration['execution_time_column_name']!== null) {
                $storageDefinition->addMethodCall('setExecutionTimeColumnName', [$storageConfiguration['execution_time_column_name']]);
            }

            $configurationDefinition->addMethodCall('setMetadataStorageConfiguration', [new Reference('doctrine.migrations.storage.table_storage')]);
        }

        if ($config['em'] !== null && $config['connection'] !== null) {
            throw new InvalidArgumentException('You can not specify both "connection" and "em" in the DoctrineMigrationsBundle configurations.');
        }

        $emID = $config['em'] !== null ? sprintf('doctrine.orm.%s_entity_manager', $config['em']) : null;

        if ($emID !== null) {
            $connectionDef = new Definition(Connection::class);
            $connectionDef->setFactory([new Reference($emID), 'getConnection']);

            $connectionId = sprintf('doctrine.migrations.connection.%s', $emID);
            $container->setDefinition($connectionId, $connectionDef);

            $em         = new Reference($emID);
            $connection = new Reference($connectionId);
        } else {
            $connectionId = sprintf('doctrine.dbal.%s_connection', $config['connection'] ?? 'default');
            $connection   = new Reference($connectionId);
            $em           = null;
        }

        $diDefinition->setArgument(1, $connection);
        $diDefinition->setArgument(2, $em);
        $diDefinition->setArgument(3, new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath() : string
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    public function getNamespace() : string
    {
        return 'http://symfony.com/schema/dic/doctrine/migrations';
    }
}
