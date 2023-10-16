<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorFactoryInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 */
interface RelationshipConfigBuilderInterface
{
    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     *
     * @return $this
     */
    public function setRelationshipType(ResourceTypeInterface $relationshipType): self;

    /**
     * @param RelationshipConstructorBehaviorFactoryInterface<TCondition> $behaviorFactory
     *
     * @return $this
     */
    public function addConstructorBehavior(RelationshipConstructorBehaviorFactoryInterface $behaviorFactory): self;

    /**
     * @param RelationshipSetBehaviorFactoryInterface<TCondition, TSorting, TEntity, TRelationship> $behaviorFactory
     *
     * @return $this
     */
    public function addPostConstructorBehavior(RelationshipSetBehaviorFactoryInterface $behaviorFactory): self;

    /**
     * @param RelationshipSetBehaviorFactoryInterface<TCondition, TSorting, TEntity, TRelationship> $behaviorFactory
     *
     * @return $this
     */
    public function addUpdateBehavior(RelationshipSetBehaviorFactoryInterface $behaviorFactory): self;
}
