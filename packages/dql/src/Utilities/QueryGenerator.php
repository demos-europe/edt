<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\Utilities;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use EDT\DqlQuerying\Contracts\ClauseInterface;
use EDT\DqlQuerying\Contracts\MappingException;
use EDT\DqlQuerying\Contracts\OrderByInterface;
use EDT\Querying\Contracts\PaginationException;

class QueryGenerator
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param class-string                             $entityClass
     * @param list<ClauseInterface>                    $conditions
     * @param list<OrderByInterface>                   $sortMethods
     * @param int<0, max>                              $offset
     * @param int<0, max>|null                         $limit
     * @param array<non-empty-string, ClauseInterface> $selections
     *
     * @throws MappingException
     * @throws PaginationException
     */
    public function generateQueryBuilder(string $entityClass, array $conditions, array $sortMethods = [], int $offset = 0, int $limit = null, array $selections = []): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $joinFinder = new JoinFinder($metadataFactory);
        $builderPreparer = new QueryBuilderPreparer($entityClass, $metadataFactory, $joinFinder);
        $builderPreparer->setSelectExpressions($selections);
        $builderPreparer->setWhereExpressions($conditions);
        $builderPreparer->setOrderByExpressions($sortMethods);
        $builderPreparer->fillQueryBuilder($queryBuilder);

        // add offset if needed
        if (0 !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        // add limit if needed
        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder;
    }
}
