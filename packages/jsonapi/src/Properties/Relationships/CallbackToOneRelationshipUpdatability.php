<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\ToOneRelationshipUpdatabilityInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToOneRelationshipUpdatabilityInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class CallbackToOneRelationshipUpdatability implements ToOneRelationshipUpdatabilityInterface
{
    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param callable(TEntity, TRelationship|null): void $updateCallback
     * @param EntityVerifierInterface<TCondition, TSorting> $entityVerifier
     */
    public function __construct(
        protected readonly array $entityConditions,
        protected readonly array $relationshipConditions,
        protected readonly TransferableTypeInterface $relationshipType,
        protected readonly mixed $updateCallback,
        protected readonly EntityVerifierInterface $entityVerifier
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

    public function updateToOneRelationship(object $entity, ?object $relationship): void
    {
        ($this->updateCallback)($entity, $relationship);
    }
}
