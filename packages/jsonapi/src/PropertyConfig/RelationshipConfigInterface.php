<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends PropertyConfigInterface<TCondition, TEntity>
 */
interface RelationshipConfigInterface extends PropertyConfigInterface
{
    /**
     * @return list<PropertySetBehaviorInterface<TEntity>>
     */
    public function getPostConstructorBehaviors(): array;

    /**
     * @return list<RelationshipSetBehaviorInterface<TCondition, TSorting, TEntity, TRelationship>>
     */
    public function getUpdateBehaviors(): array;
}
