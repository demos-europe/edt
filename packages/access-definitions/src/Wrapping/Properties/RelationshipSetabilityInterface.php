<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

/**
 * Provides updatability information and behavior for a to-many relationship property.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends PropertySetabilityInterface<TCondition, TEntity>
 * @template-extends RestrictableRelationshipInterface<TCondition>
 * @template-extends RelationshipInterface<TransferableTypeInterface<TCondition, TSorting, TRelationship>>
 */
interface RelationshipSetabilityInterface extends PropertySetabilityInterface, RestrictableRelationshipInterface, RelationshipInterface
{
}
