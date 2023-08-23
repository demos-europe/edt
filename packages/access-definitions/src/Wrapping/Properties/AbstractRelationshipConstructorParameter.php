<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\Contracts\Types\RelationshipFetchableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * @template-implements ConstructorParameterInterface<TCondition, TSorting>
 * @template-implements RestrictableRelationshipInterface<TCondition>
 * @template-implements RelationshipInterface<NamedTypeInterface&RelationshipFetchableTypeInterface<TCondition, TSorting, object>>
 */
abstract class AbstractRelationshipConstructorParameter implements ConstructorParameterInterface, RestrictableRelationshipInterface, RelationshipInterface
{
    /**
     * @param non-empty-string $argumentName
     * @param non-empty-string $propertyName
     * @param TransferableTypeInterface<TCondition, TSorting, object> $relationshipType
     * @param list<TCondition> $relationshipConditions
     */
    public function __construct(
        protected readonly string $argumentName,
        protected readonly string $propertyName,
        protected readonly TransferableTypeInterface $relationshipType,
        protected readonly array $relationshipConditions
    ) {}

    public function getRelationshipConditions(): array
    {
        return $this->relationshipConditions;
    }

    /**
     * @return NamedTypeInterface&RelationshipFetchableTypeInterface<TCondition, TSorting, object>
     */
    public function getRelationshipType(): NamedTypeInterface&RelationshipFetchableTypeInterface
    {
        return $this->relationshipType;
    }

    public function getArgumentName(): string
    {
        return $this->argumentName;
    }

    public function getRequiredAttributes(): array
    {
        return [];
    }

    public function getOptionalAttributes(): array
    {
        return [];
    }

    public function getRequiredToOneRelationships(): array
    {
        return [];
    }

    public function getOptionalToOneRelationships(): array
    {
        return [];
    }

    public function getRequiredToManyRelationships(): array
    {
        return [];
    }

    public function getOptionalToManyRelationships(): array
    {
        return [];
    }
}
