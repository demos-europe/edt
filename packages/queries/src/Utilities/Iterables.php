<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use InvalidArgumentException;
use function is_array;
use function array_slice;
use function count;
use function is_int;

/**
 * @internal
 */
class Iterables
{
    private function __construct() {}

    /**
     * Maps each given value to an array using a given callable. All arrays will be merged
     * (flatted) into a single one and returned.
     *
     * @template V
     * @template TOutput
     *
     * @param callable(V): list<TOutput> $callable how to map each given value to an array
     * @param array<int|string, V> $values   the values to be mapped to an array
     *
     * @return list<TOutput>
     */
    public static function mapFlat(callable $callable, array $values): array
    {
        // Do not remove this check: there was a problem when passing no arguments
        // to array_merge, which could not be reproduced with tests.
        $mappedArray = array_map($callable, $values);
        if ([] === $mappedArray) {
            return [];
        }

        return array_merge(...$mappedArray);
    }

    /**
     * Split the given iterable into multiple arrays.
     *
     * Can be used to revert a {@link Iterables::mapFlat} operation.
     *
     * @template TKey of int|string
     * @template V
     *
     * @param array<TKey, V> $toSplit The array to split. Length must be equal to the sum of $sizes, otherwise the behavior is undefined.
     * @param int ...$sizes        The intended array size of each item in the result array.
     *
     * @return list<array<TKey, V>> The nested result array, same length as the $sizes array.
     */
    public static function split(iterable $toSplit, int ...$sizes): array
    {
        $toSplit = self::asArray($toSplit);

        $result = [];
        $valuesOffset = 0;
        foreach ($sizes as $count) {
            /** @var array<TKey, V> $slice */
            $slice = array_slice($toSplit, $valuesOffset, $count, true);
            $result[] = $slice;
            $valuesOffset += $count;
        }

        return $result;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return array<int|string, T>
     */
    public static function asArray(iterable $iterable): array
    {
        return is_array($iterable) ? $iterable : iterator_to_array($iterable);
    }

    /**
     * @param iterable<mixed> $values
     * @throws InvalidArgumentException Thrown if the number of values in the iterable is not equal the given count value.
     */
    public static function assertCount(int $count, iterable $values): void
    {
        $valueCount = count(self::asArray($values));
        if ($count !== $valueCount) {
            throw new InvalidArgumentException("Expected exactly $count parameter, got $valueCount.");
        }
    }

    /**
     * @template T
     * @param iterable<T> $propertyValues
     * @return T
     */
    public static function getOnlyValue(iterable $propertyValues)
    {
        $array = self::asArray($propertyValues);
        if (1 !== count($array)) {
            $arrayCount = count($array);
            throw new InvalidArgumentException("Expected exactly 1 parameter, got $arrayCount.");
        }

        return array_pop($array);
    }
}
