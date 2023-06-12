<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\JsonApi\Properties\Attributes\OptionalInitializabilityTrait;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\ToOneRelationshipInitializabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends PathToOneRelationshipSetability<TCondition, TSorting, TEntity, TRelationship>
 * @template-implements ToOneRelationshipInitializabilityInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class PathToOneRelationshipInitializability extends PathToOneRelationshipSetability implements ToOneRelationshipInitializabilityInterface
{
    use OptionalInitializabilityTrait;

    /**
     * @param class-string<TEntity> $entityClass
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param non-empty-list<non-empty-string> $propertyPath
     */
    public function __construct(
        string $entityClass,
        array $entityConditions,
        array $relationshipConditions,
        TransferableTypeInterface $relationshipType,
        array $propertyPath,
        PropertyAccessorInterface $propertyAccessor
    ) {
        parent::__construct(
            $entityClass,
            $entityConditions,
            $relationshipConditions,
            $relationshipType,
            $propertyPath,
            $propertyAccessor
        );
    }
}
