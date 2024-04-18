<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * This interface defines configuration options that only available for to-many relationships.
 *
 * Besides that, its type itself can be used to denote a to-many relationship property.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, TRelationship, list<TRelationship>>
 */
interface ToManyRelationshipConfigBuilderInterface extends RelationshipConfigBuilderInterface
{
}
