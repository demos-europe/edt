<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;

/**
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 */
class SortDefinition
{
    /**
     * @var list<TSorting>
     */
    private array $sortMethods = [];

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
