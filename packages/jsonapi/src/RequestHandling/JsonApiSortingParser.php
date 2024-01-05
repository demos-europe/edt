<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * @template TSorting of PathsBasedInterface
 */
class JsonApiSortingParser
{
    /**
     * @param SortMethodFactoryInterface<TSorting> $sortMethodFactory
     */
    public function __construct(
        protected readonly SortMethodFactoryInterface $sortMethodFactory
    ) {}

    /**
     * Create an array of {@link SortMethodInterface} objects from the sort query parameter given.
     *
     * @param non-empty-string $sortQueryParamValue
     *
     * @return non-empty-list<TSorting>
     */
    public function createFromQueryParamValue(string $sortQueryParamValue): array
    {
        $sortMethodsRaw = explode(',', $sortQueryParamValue);

        return array_map([$this, 'parseSortMethod'], $sortMethodsRaw);
    }

    /**
     * @param string $sortMethodRaw
     *
     * @return TSorting
     *
     * @throws PathException
     * @throws InvalidArgumentException
     */
    protected function parseSortMethod(string $sortMethodRaw): PathsBasedInterface
    {
        Assert::stringNotEmpty($sortMethodRaw);

        return $this->isNegativeDirection($sortMethodRaw)
            ? $this->parseNegativeDirection($sortMethodRaw)
            : $this->parsePositiveDirection($sortMethodRaw);
    }


    /**
     * @param non-empty-string $sortMethodRaw
     *
     * @return TSorting
     *
     * @throws PathException
     */
    protected function parseNegativeDirection(string $sortMethodRaw): PathsBasedInterface
    {
        $pathString = substr($sortMethodRaw, 1);
        $pathArray = $this->toPathArray($pathString);
        return $this->sortMethodFactory->propertyDescending($pathArray);
    }

    /**
     * @param non-empty-string $sortMethodRaw
     *
     * @return TSorting
     *
     * @throws PathException
     */
    protected function parsePositiveDirection(string $sortMethodRaw): PathsBasedInterface
    {
        $pathArray = $this->toPathArray($sortMethodRaw);
        return $this->sortMethodFactory->propertyAscending($pathArray);
    }

    /**
     * @param non-empty-string $sortMethodRaw
     */
    protected function isNegativeDirection(string $sortMethodRaw): bool
    {
        return 0 === strncmp($sortMethodRaw, '-', 1);
    }

    /**
     * @return non-empty-list<non-empty-string>
     *
     * @throws InvalidArgumentException
     */
    protected function toPathArray(string $pathString): array
    {
        $path = explode('.', $pathString);
        Assert::allStringNotEmpty($path, "Invalid path `$pathString`, contains empty segments.");

        return $path;
    }
}
