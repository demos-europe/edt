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
     * The properties of the resource that were adjusted differently than requested.
     *
     * This may contain the `id` field if no specific ID was set by the client in a creation request, meaning the backend
     * created one itself which needs to be provided to the client.
     *
     * @return list<non-empty-string>
     */
    public function getRequestDeviations(): array
    {
        return $this->requestDeviations;
    }
}
