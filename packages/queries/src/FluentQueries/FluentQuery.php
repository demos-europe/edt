<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\OffsetEntityProviderInterface;
use EDT\Querying\Contracts\FluentQueryException;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PaginationException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\SortException;
use EDT\Querying\Pagination\OffsetPagination;
use const PHP_INT_MAX;

/**
 * A query to retrieve objects of a specific type. Can be modified before being executed.
 *
 * Instances of this class "guide" the developer through the creation and execution of the
 * query to retrieve objects.
 *
 * You may want to implement a factory to create instances of this class instead of using
 * its constructor, to avoid manually providing the same parameters on every usage.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 */
class FluentQuery
{
    /**
     * @param OffsetEntityProviderInterface<TCondition, TSorting, TEntity> $objectProvider
     * @param ConditionDefinition<TCondition> $conditionDefinition
     * @param SortDefinition<TSorting> $sortDefinition
     */
    public function __construct(
        protected readonly OffsetEntityProviderInterface $objectProvider,
        protected readonly ConditionDefinition $conditionDefinition,
        protected readonly SortDefinition $sortDefinition,
        protected readonly SliceDefinition $sliceDefinition
    ) {}

    /**
     * @return list<TEntity>
     *
     * @throws PathException
     * @throws PaginationException
     * @throws SortException
     */
    public function getEntities(): array
    {
        $offset = $this->sliceDefinition->getOffset();
        $limit = $this->sliceDefinition->getLimit();

        return $this->objectProvider->getEntities(
            $this->conditionDefinition->getConditions(),
            $this->sortDefinition->getSortMethods(),
            0 !== $offset || null !== $limit
                ? new OffsetPagination($offset, $limit ?? PHP_INT_MAX)
                : null
        );
    }

    /**
     * @return TEntity|null
     *
     * @throws FluentQueryException
     * @throws PathException
     * @throws PaginationException
     * @throws SortException
     */
    public function getUniqueEntity(): ?object
    {
        $result = $this->getEntities();

        $first = null;
        foreach ($result as $object) {
            if (null !== $first) {
                throw FluentQueryException::createNonUnique();
            }
            // minimal safeguard against invalid object provider implementation
            if (null === $object) {
                throw FluentQueryException::null();
            }
            $first = $object;
        }

        return $first;
    }

    /**
     * @return SortDefinition<TSorting>
     */
    public function getSortDefinition(): SortDefinition
    {
        return $this->sortDefinition;
    }

    /**
     * @return ConditionDefinition<TCondition>
     */
    public function getConditionDefinition(): ConditionDefinition
    {
        return $this->conditionDefinition;
    }

    public function getSliceDefinition(): SliceDefinition
    {
        return $this->sliceDefinition;
    }
}
