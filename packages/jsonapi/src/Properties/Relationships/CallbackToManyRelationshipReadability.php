<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\EntityVerificationTrait;
use EDT\Wrapping\Properties\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>>
 */
class CallbackToManyRelationshipReadability implements ToManyRelationshipReadabilityInterface
{
    use EntityVerificationTrait;

    /**
     * @param callable(TEntity): iterable<TRelationship> $readCallback
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param EntityVerifierInterface<TCondition, TSorting> $entityVerifier
     */
    public function __construct(
        protected readonly bool $defaultField,
        protected readonly bool $defaultInclude,
        protected readonly mixed $readCallback,
        protected readonly TransferableTypeInterface $relationshipType,
        protected readonly EntityVerifierInterface $entityVerifier
    ) {}

    public function isDefaultInclude(): bool
    {
        return $this->defaultInclude;
    }

    public function getRelationshipType(): TransferableTypeInterface
    {
        return $this->relationshipType;
    }

    public function isDefaultField(): bool
    {
        return $this->defaultField;
    }

    public function getValue(object $entity, array $conditions, array $sortMethods): array
    {
        $relationshipEntities = ($this->readCallback)($entity);

        $relationshipClass = $this->relationshipType->getEntityClass();
        $relationshipEntities = $this->assertValidToManyValue($relationshipEntities, $relationshipClass);
        $relationshipEntities = $this->entityVerifier
            ->filterEntities($relationshipEntities, $conditions, $this->relationshipType);
        $relationshipEntities = $this->entityVerifier
            ->sortEntities($relationshipEntities, $sortMethods, $this->relationshipType);

        return $relationshipEntities;
    }
}
