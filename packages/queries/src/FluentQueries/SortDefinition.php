<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;

/**
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 */
class SortDefinition
{
    /**
     * @var list<S>
     */
    private $sortMethods = [];
    /**
     * @var SortMethodFactoryInterface<S>
     */
    private $sortMethodFactory;

    /**
     * @param SortMethodFactoryInterface<S> $sortMethodFactory
     */
    public function __construct(SortMethodFactoryInterface $sortMethodFactory)
    {
        $this->sortMethodFactory = $sortMethodFactory;
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyAscending(string $property, string ...$properties): self
    {
        $this->sortMethods[] = $this->sortMethodFactory->propertyAscending($property, ...$properties);
        return $this;
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return $this
     */
    public function propertyDescending(string $property, string ...$properties): self
    {
        $this->sortMethods[] = $this->sortMethodFactory->propertyDescending($property, ...$properties);
        return $this;
    }

    /**
     * @return list<S>
     */
    public function getSortMethods(): array
    {
        return $this->sortMethods;
    }
}
