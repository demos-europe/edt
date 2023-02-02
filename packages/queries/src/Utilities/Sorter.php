<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\PropertyPaths\PathInfo;
use Throwable;
use Webmozart\Assert\Assert;
use function count;

/**
 * @internal
 */
class Sorter
{
    public function __construct(
        private readonly TableJoiner $tableJoiner
    ) {}

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
                     * Sorting by to-many relationships is not supported yet as it is not as easy to implement as one might think.
                     * Which of the values should be used for the comparison. See also (basically) the same problem in SQL:
                     * {@see https://www.programmerinterview.com/database-sql/sql-select-distinct-and-order-by/}
                     */
                    Assert::count($propertyValuesRowsA, 1);
                    Assert::count($propertyValuesRowsB, 1);
                    $propertyValueA = array_pop($propertyValuesRowsA);
                    $propertyValueB = array_pop($propertyValuesRowsB);
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
