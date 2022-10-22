<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\PropertyPaths\PathInfo;
use Throwable;
use function count;
use function Safe\usort;

/**
 * @internal
 */
class Sorter
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
     * @template TKey of int|string
     * @template TEntity of object
     *
     * @param array<TKey, TEntity>                $entitiesToSort
     * @param non-empty-list<SortMethodInterface> $sortMethods
     *
     * @return array<TKey, TEntity>
     *
     * @throws SortException
     */
    public function sortArray(array $entitiesToSort, array $sortMethods): array
    {
        try {
            usort($entitiesToSort, function (object $valueA, object $valueB) use ($sortMethods): int {
                foreach ($sortMethods as $sortMethod) {
                    $propertyPaths = PathInfo::getPropertyPaths($sortMethod);
                    $propertyValuesRowsA = $this->tableJoiner->getValueRows($valueA, $propertyPaths);
                    $propertyValuesRowsB = $this->tableJoiner->getValueRows($valueB, $propertyPaths);
                    /**
                     * Sorting by relationships is not supported yet as it is not as easy to implement as one might think.
                     * Which of the values should be used for the comparison. See also (basically) the same problem in SQL:
                     * {@see https://www.programmerinterview.com/database-sql/sql-select-distinct-and-order-by/}
                     */
                    $propertyValueA = Iterables::getOnlyValue($propertyValuesRowsA);
                    $propertyValueB = Iterables::getOnlyValue($propertyValuesRowsB);
                    $result = $sortMethod->evaluate($propertyValueA, $propertyValueB);
                    if (0 !== $result) {
                        return $result;
                    }
                }

                return 0;
            });

            return $entitiesToSort;
        } catch (Throwable $exception) {
            throw SortException::forCountAndMethods($exception, count($entitiesToSort), $sortMethods);
        }
    }
}
