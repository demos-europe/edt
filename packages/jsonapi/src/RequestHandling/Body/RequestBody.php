<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling\Body;

abstract class RequestBody
{
    /**
     * @param non-empty-string $type
     * @param array<non-empty-string, mixed> $attributes
     * @param array<non-empty-string, JsonApiRelationship|null> $toOneRelationships
     * @param array<non-empty-string, list<JsonApiRelationship>> $toManyRelationships
     */
    public function __construct(
        protected readonly string $type,
        protected readonly array $attributes,
        protected readonly array $toOneRelationships,
        protected readonly array $toManyRelationships
    ) {}

    /**
     * @return non-empty-string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getAllPropertyNames(): array
    {
        $properties = array_merge(
            $this->attributes,
            $this->toOneRelationships,
            $this->toManyRelationships,
        );

        return array_keys($properties);
    }

    /**
     * @return array<non-empty-string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<non-empty-string, JsonApiRelationship|null>
     */
    public function getToOneRelationships(): array
    {
        return $this->toOneRelationships;
    }

    /**
     * @return array<non-empty-string, list<JsonApiRelationship>>
     */
    public function getToManyRelationships(): array
    {
        return $this->toManyRelationships;
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function hasProperty(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->attributes)
            || array_key_exists($propertyName, $this->toOneRelationships)
            || array_key_exists($propertyName, $this->toManyRelationships);
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function getAttributeValue(string $propertyName): mixed
    {
        return $this->attributes[$propertyName] ?? throw new \InvalidArgumentException("No attribute '$propertyName' present.");
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @return JsonApiRelationship|null
     */
    public function getToOneRelationshipReference(string $propertyName): mixed
    {
        return $this->toOneRelationships[$propertyName] ?? throw new \InvalidArgumentException("No to-one relationship '$propertyName' present.");
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @return list<JsonApiRelationship>
     */
    public function getToManyRelationshipReferences(string $propertyName): array
    {
        return $this->toManyRelationships[$propertyName] ?? throw new \InvalidArgumentException("No to-many relationship '$propertyName' present.");
    }
}
