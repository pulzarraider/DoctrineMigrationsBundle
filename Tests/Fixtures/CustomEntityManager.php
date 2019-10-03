<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Fixtures;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class CustomEntityManager implements EntityManagerInterface
{
    public function getCache() : void
    {
        // TODO: Implement getCache() method.
    }

    public function getConnection()
    {
        return new class ($this) extends Connection {
            /** @var CustomEntityManager */
            private $em;

            public function __construct(CustomEntityManager $em)
            {
                $this->em = $em;
            }

            public function getEm()
            {
                return $this->em;
            }
        };
    }

    public function getExpressionBuilder() : void
    {
        // TODO: Implement getExpressionBuilder() method.
    }

    public function beginTransaction() : void
    {
        // TODO: Implement beginTransaction() method.
    }

    public function transactional($func) : void
    {
        // TODO: Implement transactional() method.
    }

    public function commit() : void
    {
        // TODO: Implement commit() method.
    }

    public function rollback() : void
    {
        // TODO: Implement rollback() method.
    }

    public function createQuery($dql = '') : void
    {
        // TODO: Implement createQuery() method.
    }

    public function createNamedQuery($name) : void
    {
        // TODO: Implement createNamedQuery() method.
    }

    public function createNativeQuery($sql, ResultSetMapping $rsm) : void
    {
        // TODO: Implement createNativeQuery() method.
    }

    public function createNamedNativeQuery($name) : void
    {
        // TODO: Implement createNamedNativeQuery() method.
    }

    public function createQueryBuilder() : void
    {
        // TODO: Implement createQueryBuilder() method.
    }

    public function getReference($entityName, $id) : void
    {
        // TODO: Implement getReference() method.
    }

    public function getPartialReference($entityName, $identifier) : void
    {
        // TODO: Implement getPartialReference() method.
    }

    public function close() : void
    {
        // TODO: Implement close() method.
    }

    public function copy($entity, $deep = false) : void
    {
        // TODO: Implement copy() method.
    }

    public function lock($entity, $lockMode, $lockVersion = null) : void
    {
        // TODO: Implement lock() method.
    }

    public function getEventManager() : void
    {
        // TODO: Implement getEventManager() method.
    }

    public function getConfiguration() : void
    {
        // TODO: Implement getConfiguration() method.
    }

    public function isOpen() : void
    {
        // TODO: Implement isOpen() method.
    }

    public function getUnitOfWork() : void
    {
        // TODO: Implement getUnitOfWork() method.
    }

    public function getHydrator($hydrationMode) : void
    {
        // TODO: Implement getHydrator() method.
    }

    public function newHydrator($hydrationMode) : void
    {
        // TODO: Implement newHydrator() method.
    }

    public function getProxyFactory() : void
    {
        // TODO: Implement getProxyFactory() method.
    }

    public function getFilters() : void
    {
        // TODO: Implement getFilters() method.
    }

    public function isFiltersStateClean() : void
    {
        // TODO: Implement isFiltersStateClean() method.
    }

    public function hasFilters() : void
    {
        // TODO: Implement hasFilters() method.
    }

    public function find($className, $id) : void
    {
        // TODO: Implement find() method.
    }

    public function persist($object) : void
    {
        // TODO: Implement persist() method.
    }

    public function remove($object) : void
    {
        // TODO: Implement remove() method.
    }

    public function merge($object) : void
    {
        // TODO: Implement merge() method.
    }

    public function clear($objectName = null) : void
    {
        // TODO: Implement clear() method.
    }

    public function detach($object) : void
    {
        // TODO: Implement detach() method.
    }

    public function refresh($object) : void
    {
        // TODO: Implement refresh() method.
    }

    public function flush() : void
    {
        // TODO: Implement flush() method.
    }

    public function getRepository($className) : void
    {
        // TODO: Implement getRepository() method.
    }

    public function getMetadataFactory() : void
    {
        // TODO: Implement getMetadataFactory() method.
    }

    public function initializeObject($obj) : void
    {
        // TODO: Implement initializeObject() method.
    }

    public function contains($object) : void
    {
        // TODO: Implement contains() method.
    }

    public function getClassMetadata($className) : void
    {
    }
}
