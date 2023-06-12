<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of FunctionInterface<bool>
 * @template TSorting of SortMethodInterface
 * @template TEntity of object
 *
 * @template-implements ReindexableTypeInterface<TCondition, TSorting, TEntity>
 */
class PhpReindexableType implements ReindexableTypeInterface
{
    /**
     * @param TypeInterface<TCondition, TSorting, TEntity>&FilteringTypeInterface<TCondition, TSorting> $type
     */
    public function __construct(
        protected readonly TypeInterface&FilteringTypeInterface $type,
        protected readonly SchemaPathProcessor $schemaPathProcessor,
        protected readonly ConditionEvaluator $conditionEvaluator,
        protected readonly Sorter $sorter
    ) {}

    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array
    {
        $entities = $this->applyFilterToEntities($entities, $conditions);
        $entities = $this->applySortingToEntities($entities, $sortMethods);

        return $entities;
    }

    public function assertMatchingEntities(array $entities, array $conditions): void
    {
        $filteredEntities = $this->applyFilterToEntities($entities, $conditions);
        Assert::count($filteredEntities, count($entities));
    }

    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        Assert::true($this->isMatchingEntity($entity, $conditions));
    }

    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        return null !== $this->applyFilterToEntity($entity, $conditions);
    }

    /**
     * @param TEntity $entity
     * @param list<TCondition> $conditions
     * @return TEntity|null
     * @throws \EDT\Querying\Contracts\PathException
     */
    protected function applyFilterToEntity(
        object $entity,
        array $conditions,
    ): ?object {
        if ([] !== $conditions) {
            $this->schemaPathProcessor->mapFilterConditions($this->type, $conditions);
        }
        $conditions[] = $this->type->getAccessCondition();

        // if access is allowed, return the entity, otherwise return null
        return $this->conditionEvaluator->evaluateConditions($entity, $conditions)
            ? $entity
            : null;
    }

    /**
     * @param list<TEntity> $entities
     * @param list<TCondition> $conditions
     * @return list<TEntity>
     * @throws \EDT\Querying\Contracts\PathException
     */
    protected function applyFilterToEntities(array $entities, array $conditions): array
    {
        if ([] !== $conditions) {
            $this->schemaPathProcessor->mapFilterConditions($this->type, $conditions);
        }
        $conditions[] = $this->type->getAccessCondition();

        return array_values($this->conditionEvaluator->filterArray($entities, ...$conditions));
    }

    /**
     * @param list<TEntity> $entities
     * @param list<TSorting> $sortMethods
     *
     * @return list<TEntity>
     *
     * @throws \EDT\Querying\Contracts\PathException
     * @throws \EDT\Querying\Contracts\SortException
     */
    protected function applySortingToEntities(
        array $entities,
        array $sortMethods,
    ): array {
        if ([] === $sortMethods) {
            $sortMethods = $this->type->getDefaultSortMethods();
        } elseif ($this->type instanceof SortingTypeInterface) {
            $this->schemaPathProcessor->mapSorting($this->type, $sortMethods);
        } else {
            throw AccessException::typeNotSortable($this->type);
        }

        if ([] === $sortMethods) {
            return $entities;
        }

        return $this->sorter->sortArray($entities, $sortMethods);
    }
}
