<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\SortException;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\PropertyPaths\PathInfo;
use function count;

/**
 * @internal
 */
class Sorter
{
    /**
     * @var TableJoiner
     */
    private $tableJoiner;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->tableJoiner = new TableJoiner($propertyAccessor);
    }

    /**
     * @param array<int,object> $arrayToSort
     * @throws SortException
     */
    public function sortArray(array &$arrayToSort, SortMethodInterface ...$sortMethods): void
    {
        $success = usort($arrayToSort, function (object $valueA, object $valueB) use ($sortMethods): int {
            foreach ($sortMethods as $sortMethod) {
                $propertyPaths = PathInfo::getPropertyPaths($sortMethod);
                $propertyValuesRowsA = $this->tableJoiner->getValueRows($valueA, ...$propertyPaths);
                $propertyValuesRowsB = $this->tableJoiner->getValueRows($valueB, ...$propertyPaths);
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

        if (!$success) {
            throw SortException::forCountAndMethods(count($arrayToSort), ...$sortMethods);
        }
    }
}
