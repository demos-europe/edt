<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\EntityVerificationTrait;
use EDT\Wrapping\Properties\ToOneRelationshipReadabilityInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class CallbackToOneRelationshipReadability implements ToOneRelationshipReadabilityInterface
{
    use EntityVerificationTrait;

    /**
     * @param callable(TEntity): (TRelationship|null) $readCallback
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param EntityVerifierInterface<TCondition, TSorting> $entityVerifier
     */
    public function __construct(
        private readonly bool $defaultField,
        private readonly bool $defaultInclude,
        private readonly mixed $readCallback,
        private readonly TransferableTypeInterface $relationshipType,
        protected readonly EntityVerifierInterface $entityVerifier
    ) {}

    public function getValue(object $entity, array $conditions): ?object
    {
        $relationshipEntity = ($this->readCallback)($entity);
        $relationshipClass = $this->relationshipType->getEntityClass();
        $relationshipEntity = $this->assertValidToOneValue($relationshipEntity, $relationshipClass);
        $relationshipEntity = $this->entityVerifier
            ->filterEntity($relationshipEntity, $conditions, $this->relationshipType);

        return $relationshipEntity;
    }

    public function isDefaultField(): bool
    {
        return $this->defaultField;
    }

    public function getRelationshipType(): TransferableTypeInterface
    {
        return $this->relationshipType;
    }

    public function isDefaultInclude(): bool
    {
        return $this->defaultInclude;
    }
}
