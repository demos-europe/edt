<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

class ModifiedEntity
{
    public function __construct(
        protected readonly object $entity,
        protected readonly bool $sideEffects
    ) {}

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function hasSideEffects(): bool
    {
        return $this->sideEffects;
    }
}
