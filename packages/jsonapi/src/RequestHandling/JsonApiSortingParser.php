<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function in_array;
use function Safe\substr;

/**
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 */
class JsonApiSortingParser
{
    /**
     * @var SortMethodFactoryInterface<TSorting>
     */
    private SortMethodFactoryInterface $sortMethodFactory;

    /**
     * @param SortMethodFactoryInterface<TSorting> $sortMethodFactory
     */
    public function __construct(SortMethodFactoryInterface $sortMethodFactory)
    {
        $this->sortMethodFactory = $sortMethodFactory;
    }

    /**
     * Create an array of {@link SortMethodInterface} objects from the sort query parameter given if not null.
     * Otherwise, returns an empty array.
     *
     * @return list<TSorting>
     */
    public function createFromQueryParamValue(?string $sortQueryParamValue): array
    {
        if (null === $sortQueryParamValue) {
            return [];
        }

        $sortMethodsRaw = explode(',', $sortQueryParamValue);

        return array_map([$this, 'parseSortMethod'], $sortMethodsRaw);
    }

    /**
     * @param non-empty-string $sortMethodRaw
     *
     * @return TSorting
     *
     * @throws PathException
     * @throws StringsException
     */
    private function parseSortMethod(string $sortMethodRaw): PathsBasedInterface
    {
        return $this->isNegativeDirection($sortMethodRaw)
            ? $this->parseNegativeDirection($sortMethodRaw)
            : $this->parsePositiveDirection($sortMethodRaw);
    }


    /**
     * @param non-empty-string $sortMethodRaw
     *
     * @return TSorting
     *
     * @throws StringsException
     * @throws PathException
     */
    private function parseNegativeDirection(string $sortMethodRaw): PathsBasedInterface
    {
        $pathString = substr($sortMethodRaw, 1);
        $pathArray = $this->toPathArray($pathString);
        return $this->sortMethodFactory->propertyDescending(...$pathArray);
    }

    /**
     * @param non-empty-string $sortMethodRaw
     *
     * @return TSorting
     *
     * @throws PathException
     */
    private function parsePositiveDirection(string $sortMethodRaw): PathsBasedInterface
    {
        $pathArray = $this->toPathArray($sortMethodRaw);
        return $this->sortMethodFactory->propertyAscending(...$pathArray);
    }

    /**
     * @param non-empty-string $sortMethodRaw
     */
    private function isNegativeDirection(string $sortMethodRaw): bool
    {
        return 0 === strncmp($sortMethodRaw, '-', 1);
    }

    /**
     * @return list<non-empty-string>
     */
    private function toPathArray(string $pathString): array
    {
        $path = explode('.', $pathString);
        if (in_array('', $path, true)) {
            throw new InvalidArgumentException("Invalid path: '$pathString'.");
        }

        return $path;
    }
}
