<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\PropertyPaths\PathInfo;
use InvalidArgumentException;
use function is_bool;

/**
 * @internal
 */
class ConditionEvaluator
{
    /**
     * @var TableJoiner
     */
    private $tableJoiner;

    public function __construct(TableJoiner $tableJoiner)
    {
        $this->tableJoiner = $tableJoiner;
    }

    /**
     * @template TEntity of object
     * @template TKey of int|string
     *
     * @param array<TKey, TEntity> $arrayToFilter must not contain `null` values
     * @param FunctionInterface<bool> $condition
     * @param FunctionInterface<bool> ...$conditions
     * @return array<TKey, TEntity> Will not contain `null` values.
     */
    public function filterArray(array $arrayToFilter, FunctionInterface $condition, FunctionInterface ...$conditions): array
    {
        array_unshift($conditions, $condition);

        // nested loop: for each item check all conditions
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

        $propertyValueRows = $this->getPropertyValueRows($target, $condition);
        foreach ($propertyValueRows as $propertyValues) {
            // $propertyValues is an array of values corresponding to $propertyPaths
            // meaning the first value is the value of the property denoted by the first path
            if ($this->assertBoolean($condition->apply($propertyValues))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param FunctionInterface<bool> $condition
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
            if ($this->assertBoolean($condition->apply($propertyValues))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<list<mixed>>
     */
    private function getPropertyValueRows(object $target, FunctionInterface $condition): array
    {
        $propertyPaths = PathInfo::getPropertyPaths($condition);

        // accesses all values of the given path and creates the cartesian product,
        return $this->tableJoiner->getValueRows($target, $propertyPaths);
    }

    /**
     * @param mixed $value
     */
    private function assertBoolean($value): bool
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException('Expected function to return bool, got \''.gettype($value).'\'');
        }

        return $value;
    }
}
