<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetabilityInterface;

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
     * @return PropertySetabilityInterface<TEntity>|null
     */
    public function getPostInstantiability(): ?PropertySetabilityInterface;

    /**
     * @return RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>|null
     */
    public function getUpdatability(): ?RelationshipSetabilityInterface;
}
