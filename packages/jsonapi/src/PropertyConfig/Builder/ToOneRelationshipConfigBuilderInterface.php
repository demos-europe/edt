<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

/**
 * This interface defines configuration options that only available for to-one relationships.
 *
 * Besides that, its type itself can be used to denote a to-one relationship property.
 *
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilderInterface<TEntity, TRelationship, TRelationship|null>
 */
interface ToOneRelationshipConfigBuilderInterface extends RelationshipConfigBuilderInterface
{
}
