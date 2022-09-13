<?php

declare(strict_types=1);

namespace EDT\Querying\FluentQueries;

use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\Contracts\SortMethodInterface;

class SortDefinition
{
    /**
     * @var list<SortMethodInterface>
     */
    private $sortMethods = [];
    /**
     * @var SortMethodFactoryInterface
     */
    private $sortMethodFactory;

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
     * @return list<SortMethodInterface>
     */
    public function getSortMethods(): array
    {
        return $this->sortMethods;
    }
}
