<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\ToManyRelationshipUpdatabilityInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToManyRelationshipUpdatabilityInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class CallbackToManyRelationshipUpdatability implements ToManyRelationshipUpdatabilityInterface
{
    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param callable(TEntity, iterable<TRelationship>): void $updateCallback
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

    public function updateToManyRelationship(object $entity, array $relationships): void
    {
        ($this->updateCallback)($entity, $relationships);
    }
}
