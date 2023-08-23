<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use function is_array;
use function array_slice;

/**
 * @internal
 */
final class Iterables
{
    private function __construct() {}

    /**
     * Maps each given value to an array using a given callable. All arrays will be merged
     * (flatted) into a single one and returned.
     *
     * @template TItem
     * @template TOutput
     *
     * @param callable(TItem): list<TOutput> $callable how to map each given value to an array
     * @param array<int|string, TItem> $values the values to be mapped to an array
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
     * @template TValue of object
     *
     * @param array<non-empty-string, TValue|null> $values
     *
     * @return array<non-empty-string, TValue>
     */
    public static function removeNull(array $values): array
    {
        return array_filter($values, static fn (?object $value): bool => null !== $value);
    }
}
