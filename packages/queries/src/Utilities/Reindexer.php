<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of FunctionInterface<bool>
 * @template TSorting of SortMethodInterface
 */
class Reindexer
{
    public function __construct(
        protected readonly ConditionEvaluator $conditionEvaluator,
        protected readonly Sorter $sorter
    ) {}

    /**
     * @template TEntity of object
     *
     * @param list<TEntity> $entities
     * @param list<TCondition> $conditions
     * @param list<TSorting> $sortMethods
     *
     * @return list<TEntity>
     *
     * @throws SortException
     */
    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array
    {
        $entities = $this->applyFilterToEntities($entities, $conditions);
        $entities = $this->applySortingToEntities($entities, $sortMethods);

        return $entities;
    }

    /**
     * @param list<TCondition> $conditions
     */
    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        Assert::true($this->isMatchingEntity($entity, $conditions));
    }

    /**
     * @param list<TCondition> $conditions
     */
    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        return null !== $this->applyFilterToEntity($entity, $conditions);
    }

    /**
     * @template TEntity of object
     *
     * @param TEntity $entity
     * @param list<TCondition> $conditions
     *
     * @return TEntity|null
     *
     * @throws PathException
     */
    protected function applyFilterToEntity(
        object $entity,
        array $conditions,
    ): ?object {
        // if access is allowed, return the entity, otherwise return null
        return $this->conditionEvaluator->evaluateConditions($entity, $conditions)
            ? $entity
            : null;
    }

    /**
     * @template TEntity of object
     *
     * @param list<TEntity> $entities
     * @param list<TCondition> $conditions
     *
     * @return list<TEntity>
     */
    protected function applyFilterToEntities(array $entities, array $conditions): array
    {
        if ([] === $conditions) {
            return $entities;
        }

        return array_values($this->conditionEvaluator->filterArray($entities, ...$conditions));
    }

    /**
     * @template TEntity of object
     *
     * @param list<TEntity> $entities
     * @param list<TSorting> $sortMethods
     *
     * @return list<TEntity>
     *
     * @throws SortException
     */
    protected function applySortingToEntities(
        array $entities,
        array $sortMethods,
    ): array {
        if ([] === $sortMethods) {
            return $entities;
        }

        return $this->sorter->sortArray($entities, $sortMethods);
    }
}
