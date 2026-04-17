<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipReadabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigInterface<TCondition, TSorting, TEntity, TRelationship>
 */
interface ToOneRelationshipConfigInterface extends RelationshipConfigInterface
{
    /**
     * @return ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>|null
     */
    public function getReadability(): ?ToOneRelationshipReadabilityInterface;
}
