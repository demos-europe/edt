<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipReadabilityInterface;

/**
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigInterface<TEntity, TRelationship>
 */
interface ToOneRelationshipConfigInterface extends RelationshipConfigInterface
{
    /**
     * @return ToOneRelationshipReadabilityInterface<TEntity, TRelationship>|null
     */
    public function getReadability(): ?ToOneRelationshipReadabilityInterface;
}
