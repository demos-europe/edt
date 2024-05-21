<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

/**
 * This interface defines configuration options that only available for to-many relationships.
 *
 * Besides that, its type itself can be used to denote a to-many relationship property.
 *
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilderInterface<TEntity, TRelationship, list<TRelationship>>
 */
interface ToManyRelationshipConfigBuilderInterface extends RelationshipConfigBuilderInterface
{
}
