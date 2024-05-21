<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;

/**
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigInterface<TEntity, TRelationship>
 */
interface ToManyRelationshipConfigInterface extends RelationshipConfigInterface
{
    /**
     * @return ToManyRelationshipReadabilityInterface<TEntity, TRelationship>|null
     */
    public function getReadability(): ?ToManyRelationshipReadabilityInterface;

}
