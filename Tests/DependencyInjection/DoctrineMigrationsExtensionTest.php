<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\DoctrineMigrationsExtension;
use Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\CustomEntityManager;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use function assert;
use function method_exists;
use function sys_get_temp_dir;

class DoctrineMigrationsExtensionTest extends TestCase
{
    public function testFullConfig() : void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMigrationsExtension();

        $config = [
            'name' => 'Doctrine Sandbox Migrations',
            'storage' => [
                'table_storage' => [
                    'table_name'                 => 'doctrine_migration_versions_test',
                    'version_column_name'        => 'doctrine_migration_column_test',
                    'version_column_length'      => 2000,
                    'executed_at_column_name'    => 'doctrine_migration_executed_at_column_test',
                    'execution_time_column_name' => 'doctrine_migration_execution_time_column_test',
                ],
            ],

            'migrations_paths' => [
                'DoctrineMigrationsTest' => 'a',
                'DoctrineMigrationsTest2' => 'b',
            ],

            'migrations' => ['Foo', 'Bar'],

            'organize_migrations' => 'BY_YEAR_AND_MONTH',

            'all_or_nothing'            => true,
            'check_database_platform'   => true,
        ];

        $extension->load(['doctrine_migrations' => $config], $container);

        $container->getDefinition('doctrine.migrations.configuration')->setPublic(true);
        $container->compile();

        $config = $container->get('doctrine.migrations.configuration');

        self::assertInstanceOf(Configuration::class, $config);
        self::assertSame('Doctrine Sandbox Migrations', $config->getName());
        self::assertSame([
            'DoctrineMigrationsTest' => 'a',
            'DoctrineMigrationsTest2' => 'b',

        ], $config->getMigrationDirectories());

        self::assertSame(['Foo', 'Bar'], $config->getMigrationClasses());
        self::assertTrue($config->isAllOrNothing());
        self::assertTrue($config->isDatabasePlatformChecked());
        self::assertTrue($config->areMigrationsOrganizedByYearAndMonth());

        $storage = $config->getMetadataStorageConfiguration();
        self::assertInstanceOf(TableMetadataStorageConfiguration::class, $storage);

        self::assertSame('doctrine_migration_versions_test', $storage->getTableName());
        self::assertSame('doctrine_migration_column_test', $storage->getVersionColumnName());
        self::assertSame(2000, $storage->getVersionColumnLength());
        self::assertSame('doctrine_migration_execution_time_column_test', $storage->getExecutionTimeColumnName());
        self::assertSame('doctrine_migration_executed_at_column_test', $storage->getExecutedAtColumnName());
    }

    public function testCustomSorter() : void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMigrationsExtension();

        $config = ['sorter' => 'my_sorter'];

        $extension->load(['doctrine_migrations' => $config], $container);

        $container->getDefinition('doctrine.migrations.di')->setPublic(true);

        $conn = $this->createMock(Connection::class);
        $container->set('doctrine.dbal.default_connection', $conn);

        $sorter = new class(){
            public function __invoke() : void
            {
            }
        };
        $container->set('my_sorter', $sorter);

        $container->compile();

        $di = $container->get('doctrine.migrations.di');
        self::assertInstanceOf(DependencyFactory::class, $di);
        self::assertSame($sorter, $di->getSorter());
    }

    public function testCustomConnection() : void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMigrationsExtension();

        $config = ['connection' => 'custom'];

        $extension->load(['doctrine_migrations' => $config], $container);

        $container->getDefinition('doctrine.migrations.di')->setPublic(true);

        $conn = $this->createMock(Connection::class);
        $container->set('doctrine.dbal.custom_connection', $conn);

        $container->compile();

        $di = $container->get('doctrine.migrations.di');
        self::assertInstanceOf(DependencyFactory::class, $di);
        self::assertSame($conn, $di->getConnection());
    }

    public function testCustomEntityManager() : void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMigrationsExtension();

        $config = ['em' => 'custom'];

        $em = new Definition(CustomEntityManager::class);
        $container->setDefinition('doctrine.orm.custom_entity_manager', $em);

        $extension->load(['doctrine_migrations' => $config], $container);

        $container->getDefinition('doctrine.migrations.di')->setPublic(true);

        $container->compile();

        $di = $container->get('doctrine.migrations.di');
        self::assertInstanceOf(DependencyFactory::class, $di);

        $em = $di->getEntityManager();
        self::assertInstanceOf(CustomEntityManager::class, $em);

        assert(method_exists($di->getConnection(), 'getEm'));
        self::assertInstanceOf(CustomEntityManager::class, $di->getConnection()->getEm());
        self::assertSame($em, $di->getConnection()->getEm());
    }

    public function testCustomMetadataStorage() : void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMigrationsExtension();

        $config = [
            'storage' => ['id' => 'mock_storage_service'],
        ];

        $mockStorage = $this->createMock(MetadataStorage::class);
        $container->set('mock_storage_service', $mockStorage);

        $conn = $this->createMock(Connection::class);
        $container->set('doctrine.dbal.default_connection', $conn);

        $extension->load(['doctrine_migrations' => $config], $container);

        $container->getDefinition('doctrine.migrations.di')->setPublic(true);

        $container->compile();

        $di = $container->get('doctrine.migrations.di');
        self::assertInstanceOf(DependencyFactory::class, $di);
        self::assertSame($mockStorage, $di->getMetadataStorage());
    }

    public function testCanNotSpecifyBothEmAndConnection() : void
    {
        $this->expectExceptionMessage('You can not specify both "connection" and "em" in the DoctrineMigrationsBundle configurations');
        $this->expectException(InvalidArgumentException::class);
        $container = $this->getContainer();
        $extension = new DoctrineMigrationsExtension();

        $config = [
            'em' => 'custom',
            'connection' => 'custom',
        ];

        $extension->load(['doctrine_migrations' => $config], $container);

        $container->getDefinition('doctrine.migrations.di')->setPublic(true);

        $container->compile();
    }

    private function getContainer() : ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.bundles' => [],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../', // src dir
        ]));
    }
}
