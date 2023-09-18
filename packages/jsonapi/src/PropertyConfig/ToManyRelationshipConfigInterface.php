<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigInterface<TCondition, TSorting, TEntity, TRelationship>
 */
interface ToManyRelationshipConfigInterface extends RelationshipConfigInterface
{
    /**
     * @return ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>|null
     */
    public function getReadability(): ?ToManyRelationshipReadabilityInterface;

}
