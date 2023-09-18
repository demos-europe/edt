<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling\Body;

use EDT\Wrapping\CreationDataInterface;

class CreationRequestBody extends RequestBody implements CreationDataInterface
{
    /**
     * @param non-empty-string|null $entityId
     * @param non-empty-string $type
     * @param array<non-empty-string, mixed> $attributes
     * @param array<non-empty-string, JsonApiRelationship|null> $toOneRelationships
     * @param array<non-empty-string, list<JsonApiRelationship>> $toManyRelationships
     */
    public function __construct(
        protected readonly ?string $entityId,
        string $type,
        array $attributes,
        array $toOneRelationships,
        array $toManyRelationships
    ) {
        parent::__construct($type, $attributes, $toOneRelationships, $toManyRelationships);
    }

    public function getEntityIdentifier(): ?string
    {
        return $this->entityId;
    }
}
