<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\JsonApi\Properties\Attributes\OptionalInitializabilityTrait;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\ToManyRelationshipInitializabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends CallbackToManyRelationshipSetability<TCondition, TSorting, TEntity, TRelationship>
 * @template-implements ToManyRelationshipInitializabilityInterface<TCondition, TSorting, TEntity, TRelationship>
 */
class CallbackToManyRelationshipInitializability extends CallbackToManyRelationshipSetability implements ToManyRelationshipInitializabilityInterface
{
    use OptionalInitializabilityTrait;

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param callable(TEntity, iterable<TRelationship>): bool $setterCallback
     */
    public function __construct(
        array $entityConditions,
        array $relationshipConditions,
        TransferableTypeInterface $relationshipType,
        mixed $setterCallback
    ) {
        parent::__construct(
            $entityConditions,
            $relationshipConditions,
            $relationshipType,
            $setterCallback
        );
    }
}
