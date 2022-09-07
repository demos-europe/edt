<?php

declare(strict_types=1);

namespace EDT\Apization\SortingParsers;

use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use function Safe\substr;

class JsonApiSortingParser
{
    /**
     * @var SortMethodFactoryInterface
     */
    private $sortMethodFactory;

    public function __construct(SortMethodFactoryInterface $sortMethodFactory)
    {
        $this->sortMethodFactory = $sortMethodFactory;
    }

    /**
     * Create an array of {@link SortMethodInterface} objects from the sort query parameter given if not null.
     * Otherwise, returns an empty array.
     *
     * @return array<int,SortMethodInterface>
     */
    public function createFromQueryParamValue(?string $sortQueryParamValue): array
    {
        if (null === $sortQueryParamValue) {
            return [];
        }

        $sortMethodsRaw = explode(',', $sortQueryParamValue);

        return array_map([$this, 'parseSortMethod'], $sortMethodsRaw);
    }

    private function parseSortMethod(string $sortMethodRaw): SortMethodInterface
    {
        return $this->isNegativeDirection($sortMethodRaw)
            ? $this->parseNegativeDirection($sortMethodRaw)
            : $this->parsePositiveDirection($sortMethodRaw);
    }


    private function parseNegativeDirection(string $sortMethodRaw): SortMethodInterface
    {
        $pathString = substr($sortMethodRaw, 1);
        $pathArray = $this->toPathArray($pathString);
        return $this->sortMethodFactory->propertyDescending(...$pathArray);
    }

    private function parsePositiveDirection(string $sortMethodRaw): SortMethodInterface
    {
        $pathArray = $this->toPathArray($sortMethodRaw);
        return $this->sortMethodFactory->propertyAscending(...$pathArray);
    }

    private function isNegativeDirection(string $sortMethodRaw): bool
    {
        return 0 === strncmp($sortMethodRaw, '-', 1);
    }

    /**
     * @return array<int, string>
     */
    private function toPathArray(string $pathString): array
    {
        return explode('.', $pathString);
    }
}
