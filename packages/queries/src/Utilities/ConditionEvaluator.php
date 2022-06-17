<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;

/**
 * @internal
 */
class ConditionEvaluator
{
    /**
     * @var TableJoiner
     */
    private $tableJoiner;

    /**
     * @param PropertyAccessorInterface<object> $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->tableJoiner = new TableJoiner($propertyAccessor);
    }

    /**
     * @template T of object
     * @param T[] $arrayToFilter must not contain `null` values
     * @param FunctionInterface<bool> $condition
     * @param FunctionInterface<bool> ...$conditions
     * @return T[] Will not contain `null` values.
     */
    public function filterArray(array $arrayToFilter, FunctionInterface $condition, FunctionInterface ...$conditions): array
    {
        array_unshift($conditions, $condition);
        return array_filter($arrayToFilter, function (object $value) use ($conditions): bool {
            foreach ($conditions as $condition) {
                if (!$this->evaluateCondition($value, $condition)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Will return `false` if `null` is given.
     *
     * @param FunctionInterface<bool> $condition
     */
    public function evaluateCondition(?object $target, FunctionInterface $condition): bool
    {
        if (null === $target) {
            return false;
        }
        $propertyPaths = Iterables::asArray($condition->getPropertyPaths());
        // accesses all values of the given path and creates the cartesian product,
        $propertyValueRows = $this->tableJoiner->getValueRows($target, ...$propertyPaths);
        foreach ($propertyValueRows as $propertyValues) {
            // $propertyValues is an array of values corresponding to $propertyPaths
            // meaning the first value is the value of the property denoted by the first path
            if (true === $condition->apply($propertyValues)) {
                return true;
            }
        }
        return false;
    }
}
