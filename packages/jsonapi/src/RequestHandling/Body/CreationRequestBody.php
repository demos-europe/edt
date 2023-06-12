<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling\Body;

class CreationRequestBody extends RequestBody
{
    /**
     * @param non-empty-string|null $id
     * @param non-empty-string $type
     * @param array<non-empty-string, mixed> $attributes
     * @param array<non-empty-string, JsonApiRelationship|null> $toOneRelationships
     * @param array<non-empty-string, list<JsonApiRelationship>> $toManyRelationships
     */
    public function __construct(
        protected readonly ?string $id,
        string $type,
        array $attributes,
        array $toOneRelationships,
        array $toManyRelationships
    ) {
        parent::__construct($type, $attributes, $toOneRelationships, $toManyRelationships);
    }

    /**
     * @return non-empty-string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }
}
