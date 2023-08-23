<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\RelationshipSetabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends AbstractToManyRelationshipSetability<TCondition, TSorting, TEntity, TRelationship>
 */
class CallbackToManyRelationshipSetability extends AbstractToManyRelationshipSetability
{
    /**
     * @param non-empty-string $propertyName the exposed property name accepted by this instance
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param callable(TEntity, iterable<TRelationship>): bool $setterCallback
     */
    public function __construct(
        string $propertyName,
        protected readonly array $entityConditions,
        protected readonly array $relationshipConditions,
        protected readonly TransferableTypeInterface $relationshipType,
        protected readonly mixed $setterCallback,
        bool $optional = false
    ) {
        parent::__construct($propertyName, $optional);
    }

    public function getEntityConditions(): array
    {
        return $this->entityConditions;
    }

    public function getRelationshipType(): TransferableTypeInterface
    {
        return $this->relationshipType;
    }

    public function getRelationshipConditions(): array
    {
        return $this->relationshipConditions;
    }

    public function updateToManyRelationship(object $entity, array $relationships): bool
    {
        return ($this->setterCallback)($entity, $relationships);
    }
}
