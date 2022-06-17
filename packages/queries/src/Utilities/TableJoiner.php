<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use Closure;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use InvalidArgumentException;
use function is_array;
use function is_int;

/**
 * @internal
 */
class TableJoiner
{
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

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
     * However the returned array containing the nested arrays will be created in the same manner
     * as an SQL left join. Meaning if $object is a blog article that has 5 comments and
     * a property path accesses the comment texts then the returned array will have
     * 3 items: [['text1'], ['text2'], ['text3']]
     *
     * If additionally the author names are accessed and the article has 2
     * authors then 6 values will be returned:
     * [['text1', 'nameA'],
     *  ['text2', 'nameA'],
     *  ['text3', 'nameA'],
     *  ['text1', 'nameB'],
     *  ['text2', 'nameB'],
     *  ['text3', 'nameB']]
     *
     * If one of the authors has for some reason 2 names (eg. real and a pseudonym)
     * then 9 values will be returned
     * [['text1', 'nameA1'],
     *  ['text2', 'nameA1'],
     *  ['text3', 'nameA1'],
     *  ['text1', 'nameB'],
     *  ['text2', 'nameB'],
     *  ['text3', 'nameB'],
     *  ['text1', 'nameA2'],
     *  ['text2', 'nameA2'],
     *  ['text3', 'nameA2']]
     *
     * @param mixed $target
     * @return mixed[][]
     */
    public function getValueRows(object $target, PropertyPathAccessInterface ...$propertyPaths): array
    {
        if ([] === $propertyPaths) {
            return [[]];
        }

        $propertyPaths = $this->setReferences(...$propertyPaths);

        // this is an array of arrays as each property path results in an array as it may access a
        // to-many property with UNPACK enabled
        /** @var array<int,array<int,mixed>|int> $valuesOfPropertyPaths */
        $valuesOfPropertyPaths = array_map(function ($propertyPath) use ($target) {
            if (is_int($propertyPath)) {
                return $propertyPath;
            }

            if ($propertyPath instanceof PropertyPathAccessInterface) {
                return $this->propertyAccessor->getValuesByPropertyPath(
                    $target,
                    $propertyPath->getAccessDepth(),
                    ...iterator_to_array($propertyPath)
                );
            }

            throw new InvalidArgumentException($propertyPath);
        }, $propertyPaths);

        return $this->cartesianProduct($valuesOfPropertyPaths);
    }

    /**
     * @param array<int,array<int,mixed>|int> $columns the columns to create the cartesian product from, each with their
     *                                                 own list of values, i.e. each column may have a different number
     *                                                 of rows. an integer instead of an array indicates a reference to another column
     *
     * @return array<int,array<int,mixed>> The resulting table with a row oriented layout (the outer items being rows and the inner items being columns).
     */
    public function cartesianProduct(array $columns): array
    {
        $deReferencedColumns = Iterables::setDeReferencing($columns);

        // Handle empty columns by removing them for now from the columns to
        // build the cartesian product from.
        $emptyColumns = array_filter($deReferencedColumns, [Iterables::class, 'isEmpty']);
        $columns = array_diff_key($columns, $emptyColumns);

        // Shortcut: if there are no columns we have nothing to do.
        if ([] === $columns) {
            return [];
        }

        // Building the cartesian product of the other columns is left to the
        // recursion, we only deal with the right column here.
        $rightColumn = array_pop($columns);
        $product = $this->cartesianProductRecursive($rightColumn, ...$columns);

        return $this->reAddEmptyColumnsAsNull($product, $emptyColumns);
    }

    /**
     * @param array<int,mixed>|int $rightColumn
     * @param array<int,mixed>|int ...$leftColumns
     *
     * @return array<int,array<int,mixed>|int>
     */
    protected function cartesianProductRecursive($rightColumn, ...$leftColumns): array
    {
        if (!is_array($rightColumn) && !is_int($rightColumn)) {
            throw new InvalidArgumentException('Columns must be either an array or an integer.');
        }

        // No column must be empty within the recursion, as empty columns need to be
        // handled in a special way.
        if ((is_array($rightColumn) && [] === $rightColumn)
            || (is_int($rightColumn) && [] === $leftColumns[$rightColumn])) {
            throw new InvalidArgumentException('Column must not be empty.');
        }

        // This is not just a shortcut but the place where the result table is
        // initially filled to be expanded in other recursion steps.
        if ([] === $leftColumns) {
            return array_map(static function ($value): array {
                return [$value];
            }, $rightColumn);
        }

        // we do have more columns to step into
        $nextRightColumn = array_pop($leftColumns);
        $wipTable = $this->cartesianProductRecursive($nextRightColumn, ...$leftColumns);
        if ([] === $wipTable) {
            throw new InvalidArgumentException('Expected recursion result to be non-empty');
        }

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
     * However this is only done for a non-reference column. If the $rightColumn
     * references another column we simply de-reference the value and add it, meaning
     * the result is a table with r rows and k + 1 columns.
     *
     * @param array<int,mixed>|int $rightColumn
     * @param array<int,array<int,mixed>> $wipTable
     * @return array<int,array<int,mixed>|int>
     */
    private function rebuildTable($rightColumn, array $wipTable): array
    {
        $rebuiltTable = [];
        if (is_array($rightColumn)) {
            foreach ($rightColumn as $value) {
                $rows = $this->addValueToRows($wipTable, $value);
                array_push($rebuiltTable, ...$rows);
            }
        } elseif (is_int($rightColumn)) {
            $rows = $this->addReferenceToRows($wipTable, $rightColumn);
            array_push($rebuiltTable, ...$rows);
        } else {
            throw new InvalidArgumentException($rightColumn);
        }

        return $rebuiltTable;
    }

    /**
     * Appends the given value to all rows in the given table.
     *
     * @param array<int,array<int,mixed>> $table
     * @param mixed $value
     * @return array<int,array<int,mixed>>
     */
    private function addValueToRows(array $table, $value): array
    {
        array_walk($table, static function (array &$row) use ($value) {
            $row[] = $value;
        });

        return $table;
    }

    /**
     * @param array<int,array<int,mixed>> $table
     * @return array<int,array<int,mixed>>
     */
    private function addReferenceToRows(array $table, int $reference): array
    {
        array_walk($table, static function (array &$row) use ($reference) {
            $row[] = $row[$reference];
        });

        return $table;
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
     * @return array<int,PropertyPathAccessInterface|int>
     */
    private function setReferences(PropertyPathAccessInterface ...$paths): array
    {
        return Iterables::setReferences(Closure::fromCallable([$this, 'equalPaths']), $paths);
    }

    private function equalPaths(PropertyPathAccessInterface $pathA, PropertyPathAccessInterface $pathB): bool
    {
        return Iterables::asArray($pathA) == Iterables::asArray($pathB)
            && $pathA->getAccessDepth() === $pathB->getAccessDepth()
            && $pathA->getSalt() === $pathB->getSalt();
    }

    /**
     * Re-add the previously removed empty columns by adding null values into each row.
     *
     * @param array<int,array<int,mixed>|int> $array
     * @param array<int,array> $emptyColumns
     * @return array<int,array<int,mixed>|int>
     */
    private function reAddEmptyColumnsAsNull(array $array, array $emptyColumns): array
    {
        array_walk($emptyColumns, static function (array $v, int $index) use (&$array): void {
            Iterables::insertValue($array, $index, null);
        });

        return $array;
    }
}
