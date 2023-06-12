<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\PropertyPaths\PathInfo;
use InvalidArgumentException;

/**
 * @internal
 */
class ConditionEvaluator
{
    public function __construct(
        protected readonly TableJoiner $tableJoiner
    ) {}

    /**
     * @template TEntity of object
     * @template TKey of int|string
     *
     * @param array<TKey, TEntity> $arrayToFilter must not contain `null` values
     * @param FunctionInterface<bool> $condition TODO: refactor to non-empty-list<FunctionInterface<bool>>
     * @param FunctionInterface<bool> ...$conditions
     * @return array<TKey, TEntity> Will not contain `null` values.
     */
    public function filterArray(array $arrayToFilter, FunctionInterface $condition, FunctionInterface ...$conditions): array
    {
        array_unshift($conditions, $condition);
        $conditions = array_values($conditions);

        // nested loop: for each item check all conditions
        return array_filter($arrayToFilter, fn (object $value): bool => $this->evaluateConditions($value, $conditions));
    }

    /**
     * @param list<FunctionInterface<bool>> $conditions
     *
     * @throws PathException
     * @throws InvalidArgumentException
     */
    public function evaluateConditions(object $target, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($target, $condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Will return `false` if `null` is given.
     *
     * @param FunctionInterface<bool> $condition
     *
     * @throws PathException
     * @throws InvalidArgumentException
     */
    public function evaluateCondition(?object $target, FunctionInterface $condition): bool
    {
        if (null === $target) {
            return false;
        }

        $propertyValueRows = $this->getPropertyValueRows($target, $condition);
        foreach ($propertyValueRows as $propertyValues) {
            // $propertyValues is an array of values corresponding to $propertyPaths
            // meaning the first value is the value of the property denoted by the first path
            if ($condition->apply($propertyValues)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param FunctionInterface<bool> $condition
     *
     * @throws PathException
     * @throws InvalidArgumentException
     */
    public function evaluateConditionInverted(?object $target, FunctionInterface $condition): bool
    {
        if (null === $target) {
            return false;
        }

        $propertyValueRows = $this->getPropertyValueRows($target, $condition);
        foreach ($propertyValueRows as $propertyValues) {
            // $propertyValues is an array of values corresponding to $propertyPaths
            // meaning the first value is the value of the property denoted by the first path
            if ($condition->apply($propertyValues)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param FunctionInterface<bool> $condition
     *
     * @return list<list<mixed>>
     *
     * @throws PathException
     * @throws InvalidArgumentException
     */
    protected function getPropertyValueRows(object $target, FunctionInterface $condition): array
    {
        $propertyPaths = PathInfo::getPropertyPaths($condition);

        // accesses all values of the given path and creates the cartesian product,
        return $this->tableJoiner->getValueRows($target, $propertyPaths);
    }
}
