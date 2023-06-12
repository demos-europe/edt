<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\ToManyRelationshipUpdatabilityInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements ToManyRelationshipUpdatabilityInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class PathToManyRelationshipUpdatability implements ToManyRelationshipUpdatabilityInterface
{
    /**
     * @param class-string<TEntity> $entityClass
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param non-empty-list<non-empty-string> $propertyPath
     * @param EntityVerifierInterface<TCondition, TSorting> $entityVerifier
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly array $entityConditions,
        protected readonly array $relationshipConditions,
        protected readonly TransferableTypeInterface $relationshipType,
        protected readonly array $propertyPath,
        protected readonly PropertyAccessorInterface $propertyAccessor,
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
        $propertyPath = $this->propertyPath;
        $propertyName = array_pop($propertyPath);
        $target = [] === $propertyPath
            ? $entity
            : $this->propertyAccessor->getValueByPropertyPath($entity, ...$propertyPath);
        Assert::object($target);
        $this->propertyAccessor->setValue($target, $relationships, $propertyName);
    }
}
