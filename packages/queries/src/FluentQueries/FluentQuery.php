<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\ObjectProviderInterface;
use EDT\Querying\Contracts\FluentQueryException;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PaginationException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\SortException;

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
     * @param ObjectProviderInterface<TCondition, TSorting, TEntity> $objectProvider
     * @param ConditionDefinition<TCondition> $conditionDefinition
     * @param SortDefinition<TSorting> $sortDefinition
     */
    public function __construct(
        protected readonly ObjectProviderInterface $objectProvider,
        private readonly ConditionDefinition $conditionDefinition,
        private readonly SortDefinition $sortDefinition,
        private readonly SliceDefinition $sliceDefinition
    ) {}

    /**
     * @return iterable<TEntity>
     *
     * @throws PathException
     * @throws PaginationException
     * @throws SortException
     */
    public function getEntities(): iterable
    {
        return $this->objectProvider->getObjects(
            $this->conditionDefinition->getConditions(),
            $this->sortDefinition->getSortMethods(),
            $this->sliceDefinition->getOffset(),
            $this->sliceDefinition->getLimit()
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
            // @phpstan-ignore-next-line
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
