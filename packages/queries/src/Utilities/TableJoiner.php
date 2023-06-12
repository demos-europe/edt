<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use InvalidArgumentException;
use Webmozart\Assert\Assert;
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
    public function __construct(
        protected readonly PropertyAccessorInterface $propertyAccessor
    ) {}

    /**
     * Gets the values from the given object the property paths point to.
     * Because a property path can lead over or to an iterable property
     * the result will be a nested array. The values in the array will be
     * an array of values corresponding to the property paths (hence all values will have
     * the same length which is the length of the given $propertyPaths
     * array).
     *
     * However, the returned array containing the nested arrays will be created in the same manner
     * as an SQL left join. Meaning if `$object` is a blog article that has 3 comments and
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
     * @throws InvalidArgumentException
     */
    public function getValueRows(object $target, array $propertyPaths): array
    {
        if ([] === $propertyPaths) {
            return [[]];
        }

        $propertyPaths = $this->setReferences($propertyPaths);

        // this is an array of arrays as each property path results in an array as it may access a
        // to-many property with UNPACK enabled
        $valuesOfPropertyPaths = array_map(function (PropertyPathAccessInterface|int $propertyPath) use ($target): array|int {
            if (is_int($propertyPath)) {
                return $propertyPath;
            }

            if (null !== $propertyPath->getContext()) {
                // TODO: implement support for custom path contexts
                throw new InvalidArgumentException("Custom path contexts are not supported in PHP evaluation yet: '{$propertyPath->getAsNamesInDotNotation()}'");
            }

            return $this->propertyAccessor->getValuesByPropertyPath(
                $target,
                $propertyPath->getAccessDepth(),
                $propertyPath->getAsNames()
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
     *
     * @throws InvalidArgumentException
     */
    protected function cartesianProduct(array $columns): array
    {
        // Handle empty columns (i.e. columns without rows) by removing them for now from the columns to
        // build the cartesian product from.
        $deReferencedColumns = $this->setDeReferencing($columns);
        $emptyColumns = array_filter($deReferencedColumns, static fn (array $value): bool => [] === $value);

        // Remove empty columns and references to empty columns
        /** @var list<NonEmptyColumn|Ref> $nonEmptyColumns TODO: remove this line when phpstan can detect the type by itself */
        $nonEmptyColumns = array_values(array_diff_key($columns, $emptyColumns));

        // if there are no non-empty columns we have nothing to do.
        if ([] === $nonEmptyColumns) {
            return [];
        }

        // Building the cartesian product of the non-empty columns is left to the
        // recursion.
        $mostLeftColumn = array_shift($nonEmptyColumns);
        Assert::isArray($mostLeftColumn, "Most left column must not be a reference, was: '%s'.");
        $product = $this->cartesianProductRecursive($mostLeftColumn, $nonEmptyColumns);

        /** @var list<Ref> $emptyColumnIndices TODO: remove this line when phpstan can detect that `array_keys(list<X>)` results in` list<int<0, max>>` */
        $emptyColumnIndices = array_values(array_keys($emptyColumns));

        return $this->reAddEmptyColumnsAsNull($product, $emptyColumnIndices);
    }

    /**
     * No given column must be empty, as empty columns need to be handled in a special way.
     *
     * @param NonEmptyColumn $mostLeftColumn
     * @param list<NonEmptyColumn|Ref> $leftColumns
     *
     * @return non-empty-list<NonEmptyRow>
     */
    protected function cartesianProductRecursive(array $mostLeftColumn, array $leftColumns): array
    {
        // This is not just a shortcut but the place where the result table is
        // initially filled to be expanded in previous recursion steps.
        if ([] === $leftColumns) {
            return array_map(static fn ($value): array => [$value], $mostLeftColumn);
        }

        // we do have at least one more columns to step into
        $rightColumn = array_pop($leftColumns);
        $wipTable = $this->cartesianProductRecursive($mostLeftColumn, $leftColumns);

        return $this->rebuildTable($rightColumn, $wipTable);
    }

    /**
     * We create a new table: For each value in our current `$rightColumn` we
     * append all the already generated rows. Meaning if we had
     * * `r` rows in our `$wipTable` with `k` column
     * * and `l` lines in our $rightColumn we get a table with `r * l` rows and `k + 1` columns.
     *
     * However, this is only done for a non-reference column. If the `$rightColumn`
     * references another column (i.e. is an integer) we simply de-reference the value and add it, meaning
     * the result is a table with `r` rows and `k + 1` columns.
     *
     * @param NonEmptyColumn|Ref $rightColumn
     * @param non-empty-list<NonEmptyRow> $wipTable
     *
     * @return non-empty-list<NonEmptyRow>
     */
    protected function rebuildTable(array|int $rightColumn, array $wipTable): array
    {
        if (is_array($rightColumn)) {
            $nestedRows = array_map(
                fn (mixed $value): array => $this->addValueToRows($wipTable, $value),
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
     *
     * @return non-empty-list<NonEmptyRow>
     */
    protected function addValueToRows(array $rows, mixed $value): array
    {
        array_walk($rows, static fn (array &$row) => $row[] = $value);

        return $rows;
    }

    /**
     * @param non-empty-list<NonEmptyRow> $rows
     * @param Ref                         $reference
     *
     * @return non-empty-list<NonEmptyRow>
     */
    protected function addReferenceToRows(array $rows, int $reference): array
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
    protected function setReferences(array $paths): array
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
    protected function setReferencesGeneric(callable $equalityComparison, array $values): array
    {
        $count = count($values);
        for ($fullLoopIndex = 0; $fullLoopIndex < $count; $fullLoopIndex++) {
            $valueToCheckIfToUseAsIndex = $values[$fullLoopIndex];
            if (!is_int($valueToCheckIfToUseAsIndex)) {
                for ($remainLoopIndex = $fullLoopIndex + 1; $remainLoopIndex < $count; $remainLoopIndex++) {
                    $valueToCheckAgainst = $values[$remainLoopIndex];
                    if (!is_int($valueToCheckAgainst)
                        && $equalityComparison($valueToCheckIfToUseAsIndex, $valueToCheckAgainst)) {
                        $values[$remainLoopIndex] = $fullLoopIndex;
                    }
                }
            }
        }
        return $values;
    }

    /**
     * @throws PathException
     */
    protected function equalPaths(PropertyPathAccessInterface $pathA, PropertyPathAccessInterface $pathB): bool
    {
        return $pathA->getAsNames() == $pathB->getAsNames()
            && $pathA->getAccessDepth() === $pathB->getAccessDepth()
            && $pathA->getSalt() === $pathB->getSalt()
            && $pathA->getContext() === $pathB->getContext();
    }

    /**
     * Re-add the previously removed empty columns by adding `null` values into each row.
     *
     * @param non-empty-list<NonEmptyRow> $rows
     * @param list<Ref> $emptyColumnIndices
     *
     * @return non-empty-list<NonEmptyRow>
     */
    protected function reAddEmptyColumnsAsNull(array $rows, array $emptyColumnIndices): array
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
     * @template TValue
     *
     * @param array<string|int, list<TValue>> $array
     * @param TValue $value
     */
    protected function insertValue(array &$array, int $index, mixed $value): void
    {
        array_walk($array, static fn (array &$arrayValue) => array_splice($arrayValue, $index, 0, [$value]));
    }

    /**
     * Undoes {@link TableJoiner::setReferencesGeneric()}.
     *
     * @param non-empty-list<Column|Ref> $columns
     *
     * @return non-empty-list<Column>
     *
     * @throws InvalidArgumentException if the given array contains invalid references
     */
    protected function setDeReferencing(array $columns): array
    {
        return array_map(static function (array|int $column) use ($columns) {
            if (!is_int($column)) {
                return $column;
            }

            Assert::keyExists($columns, $column, "Could not de-reference: missing index '%s'.");

            $referencedColumn = $columns[$column];
            if (is_int($referencedColumn)) {
                throw new InvalidArgumentException("De-referencing '$column' led to another reference '$referencedColumn'.");
            }

            return $referencedColumn;
        }, $columns);
    }
}
