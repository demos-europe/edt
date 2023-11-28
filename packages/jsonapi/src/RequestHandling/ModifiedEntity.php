<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

class ModifiedEntity
{
    /**
     * @param list<non-empty-string> $requestDeviations
     */
    public function __construct(
        protected readonly object $entity,
        protected readonly array $requestDeviations
    ) {}

    public function getEntity(): object
    {
        return $this->entity;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getRequestDeviations(): array
    {
        return $this->requestDeviations;
    }
}
