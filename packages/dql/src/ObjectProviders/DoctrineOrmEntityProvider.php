<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ObjectProviders;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use EDT\DqlQuerying\Contracts\ClauseInterface;
use EDT\DqlQuerying\Contracts\MappingException;
use EDT\DqlQuerying\Utilities\JoinFinder;
use EDT\DqlQuerying\Utilities\QueryBuilderPreparer;
use EDT\DqlQuerying\Utilities\QueryGenerator;
use EDT\DqlQuerying\Contracts\OrderByInterface;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\Contracts\ObjectProviderInterface;
use EDT\Querying\Contracts\PaginationException;
use EDT\Querying\EntityProviders\OffsetPaginatingEntityProviderInterface;

/**
 * @template TEntity of object
 * @template-implements ObjectProviderInterface<ClauseInterface, OrderByInterface, TEntity>
 * @template-implements OffsetPaginatingEntityProviderInterface<ClauseInterface, OrderByInterface, TEntity>
 */
class DoctrineOrmEntityProvider implements ObjectProviderInterface, OffsetPaginatingEntityProviderInterface
{
    /**
     * @var QueryGenerator
     */
    private $queryGenerator;

    /**
     * @var QueryBuilderPreparer
     */
    private $builderPreparer;

    /**
     * @param class-string<TEntity> $className
     */
    public function __construct(string $className, EntityManager $entityManager)
    {
        $metadataFactory = $entityManager->getMetadataFactory();
        $this->queryGenerator = new QueryGenerator($entityManager);
        $this->builderPreparer = new QueryBuilderPreparer($className, $metadataFactory, new JoinFinder($metadataFactory));
    }

    /**
     * @throws MappingException
     * @throws PaginationException
     */
    public function getObjects(array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null): iterable
    {
        $queryBuilder = $this->generateQueryBuilder($conditions, $sortMethods, $offset, $limit);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param list<ClauseInterface>  $conditions
     * @param list<OrderByInterface> $sortMethods
     * @param OffsetPagination|null  $pagination
     *
     * @return iterable<TEntity>
     *
     * @throws MappingException
     * @throws PaginationException
     */
    public function getEntities(array $conditions, array $sortMethods, ?object $pagination): iterable
    {
        if (null === $pagination) {
            $offset = 0;
            $limit = null;
        } else {
            $offset = $pagination->getOffset();
            $limit = $pagination->getLimit();
        }

        $queryBuilder = $this->generateQueryBuilder($conditions, $sortMethods, $offset, $limit);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param list<ClauseInterface>  $conditions
     * @param list<OrderByInterface> $sortMethods
     * @param int<0, max>            $offset
     * @param int<0, max>|null       $limit
     *
     * @throws MappingException
     * @throws PaginationException
     */
    public function generateQueryBuilder(
        array $conditions,
        array $sortMethods = [],
        int $offset = 0,
        int $limit = null
    ): QueryBuilder {
        return $this->queryGenerator->generateQueryBuilder(
            $this->builderPreparer,
            $conditions,
            $sortMethods,
            $offset,
            $limit
        );
    }
}
