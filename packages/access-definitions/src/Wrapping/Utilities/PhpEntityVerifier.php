<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template TCondition of FunctionInterface<bool>
 * @template TSorting of SortMethodInterface
 *
 * @template-extends AbstractEntityVerifier<TCondition, TSorting>
 */
class PhpEntityVerifier extends AbstractEntityVerifier
{
    public function __construct(
        protected readonly SchemaPathProcessor $schemaPathProcessor,
        protected readonly ConditionEvaluator $conditionEvaluator,
        protected readonly Sorter $sorter
    ) {}

    protected function applyFilterToEntity(
        object $entity,
        array $conditions,
        FilterableTypeInterface $type
    ): ?object {
        if ([] !== $conditions) {
            $this->schemaPathProcessor->mapFilterConditions($type, $conditions);
        }
        $conditions[] = $type->getAccessCondition();

        // if access is allowed, return the entity, otherwise return null
        return $this->conditionEvaluator->evaluateConditions($entity, $conditions)
            ? $entity
            : null;
    }

    protected function applyFilterToEntities(
        array $entities,
        array $conditions,
        FilterableTypeInterface $type
    ): array {
        if ([] !== $conditions) {
            $this->schemaPathProcessor->mapFilterConditions($type, $conditions);
        }
        $conditions[] = $type->getAccessCondition();

        return array_values($this->conditionEvaluator->filterArray($entities, ...$conditions));
    }

    protected function applySortingToEntities(
        array $entities,
        array $sortMethods,
        TypeInterface $type
    ): array {
        if ([] === $sortMethods) {
            $sortMethods = $type->getDefaultSortMethods();
        } elseif ($type instanceof SortableTypeInterface) {
            $this->schemaPathProcessor->mapSorting($type, $sortMethods);
        } else {
            throw AccessException::typeNotSortable($type);
        }

        if ([] === $sortMethods) {
            return $entities;
        }

        return $this->sorter->sortArray($entities, $sortMethods);
    }
}
