<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorInterface;

/**
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends PropertyConfigInterface<TEntity>
 */
interface RelationshipConfigInterface extends PropertyConfigInterface
{
    /**
     * @return list<PropertySetBehaviorInterface<TEntity>>
     */
    public function getPostConstructorBehaviors(): array;

    /**
     * @return list<RelationshipSetBehaviorInterface<TEntity, TRelationship>>
     */
    public function getUpdateBehaviors(): array;
}
