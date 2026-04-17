<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\SortMethodFactoryInterface;

/**
 * @template TSorting of PathsBasedInterface
 */
class SortDefinition
{
    /**
     * @var list<TSorting>
     */
    private array $sortMethods = [];

    /**
     * @param SortMethodFactoryInterface<TSorting> $sortMethodFactory
     */
    public function __construct(
        protected readonly SortMethodFactoryInterface $sortMethodFactory
    ) {}

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyAscending(array $properties): self
    {
        $this->sortMethods[] = $this->sortMethodFactory->propertyAscending($properties);
        return $this;
    }

    /**
     * @param non-empty-list<non-empty-string> $properties
     *
     * @return $this
     */
    public function propertyDescending(array $properties): self
    {
        $this->sortMethods[] = $this->sortMethodFactory->propertyDescending($properties);
        return $this;
    }

    /**
     * @return list<TSorting>
     */
    public function getSortMethods(): array
    {
        return $this->sortMethods;
    }
}
