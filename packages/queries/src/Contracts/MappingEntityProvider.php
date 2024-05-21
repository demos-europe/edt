<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\JsonApi\InputHandling\ConditionConverter;
use EDT\JsonApi\InputHandling\SortMethodConverter;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\SortMethodFactories\SortMethodInterface;

/**
 * @template TEntity of object
 *
 * @template-implements OffsetEntityProviderInterface<DrupalFilterInterface, SortMethodInterface, TEntity>
 */
class MappingEntityProvider implements OffsetEntityProviderInterface
{
    /**
     * @template TCondition
     * @template TSorting
     *
     * @param ConditionConverter<TCondition> $conditionConverter
     * @param SortMethodConverter<TSorting> $sortMethodConverter
     * @param OffsetEntityProviderInterface<TCondition, TSorting, TEntity> $entityProvider
     */
    public function __construct(
        protected readonly ConditionConverter $conditionConverter,
        protected readonly SortMethodConverter $sortMethodConverter,
        protected readonly OffsetEntityProviderInterface $entityProvider
    ) {}

    public function getEntities(array $conditions, array $sortMethods, ?OffsetPagination $pagination): array
    {
        $conditions = $this->conditionConverter->convertConditions($conditions);
        $sortMethods = $this->sortMethodConverter->convertSortMethods($sortMethods);

        return $this->entityProvider->getEntities($conditions, $sortMethods, $pagination);
    }
}
