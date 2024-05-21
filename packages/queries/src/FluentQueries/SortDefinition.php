<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\SortMethodFactories\SortMethodInterface;

class SortDefinition
{
    /**
     * @var list<SortMethodInterface>
     */
    private array $sortMethods = [];

    /**
     * @param SortMethodFactoryInterface<SortMethodInterface> $sortMethodFactory
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
     * @return list<SortMethodInterface>
     */
    public function getSortMethods(): array
    {
        return $this->sortMethods;
    }
}
