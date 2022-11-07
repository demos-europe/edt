<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use InvalidArgumentException;
use function array_key_exists;
use function count;
use function is_array;
use function is_int;

/**
 * @phpstan-type Ref = int<0, max>
 * @phpstan-type Row = list<mixed>
 * @phpstan-type NonEmptyRow = non-empty-list<mixed>
 * @phpstan-type Column = list<mixed>
 * @phpstan-type NonEmptyColumn = non-empty-list<mixed>
 *
 * @internal
 */
class TableJoiner
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Gets the values from the given object the property paths point to.
     * Because a property path can lead over or to an iterable property
     * the result will be a nested array. The values in the array will be
     * an array of values corresponding to the property paths (hence all values will have
     * the same length which is the length of the given $propertyPaths
     * array).
     *
     * However, the returned array containing the nested arrays will be created in the same manner
     * as an SQL left join. Meaning if $object is a blog article that has 5 comments and
     * a property path accesses the comment texts then the returned array will have
     * 3 items: `[['text1'], ['text2'], ['text3']]`
     *
     * If additionally the author names are accessed and the article has 2
     * authors then 6 values will be returned:
     *
     * ```
     * [['text1', 'nameA'],
     *  ['text2', 'nameA'],
     *  ['text3', 'nameA'],
     *  ['text1', 'nameB'],
     *  ['text2', 'nameB'],
     *  ['text3', 'nameB']]
     * ```
     *
     * If one of the authors has for some reason 2 names (e.g. real and a pseudonym)
     * then 9 values will be returned:
     *
     * ```
     * [['text1', 'nameA1'],
     *  ['text2', 'nameA1'],
     *  ['text3', 'nameA1'],
     *  ['text1', 'nameB'],
     *  ['text2', 'nameB'],
     *  ['text3', 'nameB'],
     *  ['text1', 'nameA2'],
     *  ['text2', 'nameA2'],
     *  ['text3', 'nameA2']]
     * ```
     *
     * @param list<PropertyPathAccessInterface> $propertyPaths
     *
     * @return list<Row>
     *
     * @throws PathException
     */
    public function getValueRows(object $target, array $propertyPaths): array
    {
        if ([] === $propertyPaths) {
            return [[]];
        }

        $propertyPaths = $this->setReferences($propertyPaths);

        // this is an array of arrays as each property path results in an array as it may access a
        // to-many property with UNPACK enabled
        $valuesOfPropertyPaths = array_map(function ($propertyPath) use ($target) {
            if (is_int($propertyPath)) {
                return $propertyPath;
            }

            if (null !== $propertyPath->getContext()) {
                throw new InvalidArgumentException("Custom path contexts are not supported in PHP evaluation yet: '{$propertyPath->getAsNamesInDotNotation()}'");
            }

            $propertyPathNames = $propertyPath->getAsNames();
            if ([] === $propertyPathNames) {
                throw new InvalidArgumentException("Path must not be empty.");
            }

            return $this->propertyAccessor->getValuesByPropertyPath(
                $target,
                $propertyPath->getAccessDepth(),
                $propertyPathNames
            );
        }, $propertyPaths);

        return $this->cartesianProduct($valuesOfPropertyPaths);
    }

    /**
     * @param non-empty-list<Column|Ref> $columns the columns to create the cartesian product from, each with their
     *                                            own list of values, i.e. each column may have a different number
     *                                            of rows. an integer instead of an array indicates a reference to another column
     *
     * @return list<NonEmptyRow> The resulting table with a row oriented layout (the outer items being rows and the inner items being columns).
     */
    private function cartesianProduct(array $columns): array
    {
        // Handle empty columns by removing them for now from the columns to
        // build the cartesian product from.
        $deReferencedColumns = $this->setDeReferencing($columns);
        $emptyColumns = array_filter($deReferencedColumns, [$this, 'isEmptyArray']);

        // Remove empty columns and references to empty columns
        /** @var list<NonEmptyColumn|Ref> $nonEmptyColumns */
        $nonEmptyColumns = array_values(array_diff_key($columns, $emptyColumns));

        // if there are no columns we have nothing to do.
        if ([] === $nonEmptyColumns) {
            return [];
        }

        // Building the cartesian product of the other columns is left to the
        // recursion, we only deal with the right column here.
        $rightColumn = array_pop($nonEmptyColumns);
        $product = $this->cartesianProductRecursive($rightColumn, $nonEmptyColumns);

        // TODO: remove type-hint when phpstan can detect that `array_keys(list<X>)` results in` list<int<0, max>>`
        /** @var list<Ref> $emptyColumnIndices */
        $emptyColumnIndices = array_values(array_keys($emptyColumns));

        return $this->reAddEmptyColumnsAsNull($product, $emptyColumnIndices);
    }

    /**
     * This method is **not** intended as a general replacement for empty checks but intended to be used as callback, e.g.
     * ```
     * array_filter($array, [Iterables::class, 'isEmpty']);
     * ```
     *
     * Technically it would be possible to use this method in `if` conditions too,
     * but this is discouraged because it can be considered less readable than a
     * simple `if ([] === $array) {`.
     *
     * @param array<string|int, mixed> $value
     */
    private function isEmptyArray(array $value): bool
    {
        return [] === $value;
    }

    /**
     * No given column must be empty, as empty columns need to be handled in a special way.
     *
     * @param NonEmptyColumn|Ref       $rightColumn
     * @param list<NonEmptyColumn|Ref> $leftColumns
     *
     * @return non-empty-list<NonEmptyRow>
     */
    protected function cartesianProductRecursive($rightColumn, array $leftColumns): array
    {
        // This is not just a shortcut but the place where the result table is
        // initially filled to be expanded in other recursion steps.
        if ([] === $leftColumns) {
            if (!is_array($rightColumn)) {
                throw new InvalidArgumentException("Most left column must not be a reference, was: '$rightColumn'.");
            }

            return array_map(
                static fn ($value): array => [$value],
                $rightColumn
            );
        }

        // we do have more columns to step into
        $nextRightColumn = array_pop($leftColumns);
        $wipTable = $this->cartesianProductRecursive($nextRightColumn, $leftColumns);

        return $this->rebuildTable($rightColumn, $wipTable);
    }

    /**
     * We create a new table: For each value in our current $rightColumn we
     * append it to all to the already generated rows. Meaning if we had
     * * `r` rows in our $wipTable with `k` column
     * * and `l` lines in our $rightColumn
     *
     * we get a table with r * l rows and k + 1 columns.
     *
     * However, this is only done for a non-reference column. If the $rightColumn
     * references another column we simply de-reference the value and add it, meaning
     * the result is a table with r rows and k + 1 columns.
     *
     * @param NonEmptyColumn|Ref          $rightColumn
     * @param non-empty-list<NonEmptyRow> $wipTable
     *
     * @return non-empty-list<NonEmptyRow>
     */
    private function rebuildTable($rightColumn, array $wipTable): array
    {
        if (is_array($rightColumn)) {
            $nestedRows = array_map(
                fn ($value): array => $this->addValueToRows($wipTable, $value),
                $rightColumn
            );

            return array_merge(...$nestedRows);
        }

        return $this->addReferenceToRows($wipTable, $rightColumn);
    }

    /**
     * Appends the given value to all rows in the given table.
     *
     * @param non-empty-list<NonEmptyRow> $rows
     * @param mixed                       $value
     *
     * @return non-empty-list<NonEmptyRow>
     */
    private function addValueToRows(array $rows, $value): array
    {
        array_walk($rows, static function (array &$row) use ($value): void {
            $row[] = $value;
        });

        return $rows;
    }

    /**
     * @param non-empty-list<NonEmptyRow> $rows
     * @param Ref                         $reference
     *
     * @return non-empty-list<NonEmptyRow>
     */
    private function addReferenceToRows(array $rows, int $reference): array
    {
        return array_map(static function (array $row) use ($reference): array {
            $row[] = $row[$reference];

            return $row;
        }, $rows);
    }

    /**
     * Replaces duplicated paths with integers marking the position
     * of the original.
     *
     * If for example the following array is given: `[['name','title','name']]`
     * Then the result will be `[['name','title',0]]`;
     *
     * The {@link PropertyPathAccessInterface::getAccessDepth() access depth} will
     * be considered as well, meaning the replacement will only happen if
     * both paths define the same access depth.
     *
     * @param non-empty-list<PropertyPathAccessInterface> $paths
     *
     * @return non-empty-list<PropertyPathAccessInterface|Ref>
     */
    private function setReferences(array $paths): array
    {
        return $this->setReferencesGeneric([$this, 'equalPaths'], $paths);
    }


    /**
     * Compares all values with each other using the given `$equalityComparison`
     * callback. If equal values are found, the one with the higher index will be
     * replaced with the index of the first occurrence in the array.
     *
     * The type T of the given values must not be `int`.
     *
     * @template T
     * @param callable(T, T): bool $equalityComparison
     * @param non-empty-list<T> $values
     * @return non-empty-list<T|int<0, max>>
     */
    private function setReferencesGeneric(callable $equalityComparison, array $values): array
    {
        $count = count($values);
        for ($i = 0; $i < $count; $i++) {
            $valueToCheckIfToUseAsIndex = $values[$i];
            if (!is_int($valueToCheckIfToUseAsIndex)) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $valueToCheckAgainst = $values[$j];
                    if (!is_int($valueToCheckAgainst)
                        && $equalityComparison($valueToCheckIfToUseAsIndex, $valueToCheckAgainst)) {
                        $values[$j] = $i;
                    }
                }
            }
        }
        return $values;
    }

    /**
     * @throws PathException
     */
    private function equalPaths(PropertyPathAccessInterface $pathA, PropertyPathAccessInterface $pathB): bool
    {
        return $pathA->getAsNames() == $pathB->getAsNames()
            && $pathA->getAccessDepth() === $pathB->getAccessDepth()
            && $pathA->getSalt() === $pathB->getSalt()
            && $pathA->getContext() === $pathB->getContext();
    }

    /**
     * Re-add the previously removed empty columns by adding null values into each row.
     *
     * @param non-empty-list<NonEmptyRow> $rows
     * @param list<Ref>                   $emptyColumnIndices
     *
     * @return non-empty-list<NonEmptyRow>
     */
    private function reAddEmptyColumnsAsNull(array $rows, array $emptyColumnIndices): array
    {
        foreach ($emptyColumnIndices as $index) {
            $this->insertValue($rows, $index, null);
        }

        return $rows;
    }

    /**
     * Will iterate through the given array and inserts the given value into each of its values
     * (which are expected to be an array too).
     *
     * @param array<string|int, list<mixed>> $array
     * @param mixed $value
     */
    private function insertValue(array &$array, int $index, $value): void
    {
        array_walk($array, static function (&$arrayValue) use ($index, $value): void {
            array_splice($arrayValue, $index, 0, [$value]);
        });
    }

    /**
     * Undoes {@link TableJoiner::setReferencesGeneric()}.
     *
     * @param non-empty-list<Column|Ref> $columns
     *
     * @return non-empty-list<Column>
     */
    private function setDeReferencing(array $columns): array
    {
        return array_map(static function ($column) use ($columns) {
            if (!is_int($column)) {
                return $column;
            }

            if (!array_key_exists($column, $columns)) {
                throw new InvalidArgumentException("Could not de-reference: missing index '$column'.");
            }

            $referencedColumn = $columns[$column];
            if (is_int($referencedColumn)) {
                throw new InvalidArgumentException("De-referencing '$column' led to another reference '$referencedColumn'.");
            }

            return $referencedColumn;
        }, $columns);
    }
}
