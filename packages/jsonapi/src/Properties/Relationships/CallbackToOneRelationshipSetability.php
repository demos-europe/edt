<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\ToOneRelationshipSetabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToOneRelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class CallbackToOneRelationshipSetability implements ToOneRelationshipSetabilityInterface
{
    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param callable(TEntity, TRelationship|null): bool $setterCallback
     */
    public function __construct(
        protected readonly array $entityConditions,
        protected readonly array $relationshipConditions,
        protected readonly TransferableTypeInterface $relationshipType,
        protected readonly mixed $setterCallback,
    ) {}

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

    public function updateToOneRelationship(object $entity, ?object $relationship): bool
    {
        return ($this->setterCallback)($entity, $relationship);
    }
}
