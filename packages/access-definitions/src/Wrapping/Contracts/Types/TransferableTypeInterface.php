<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\RelationshipUpdatability;
use EDT\Wrapping\Properties\ToManyRelationshipReadability;
use EDT\Wrapping\Properties\ToOneRelationshipReadability;
use EDT\Wrapping\Properties\Updatability;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends TypeInterface<TCondition, TSorting, TEntity>
 */
interface TransferableTypeInterface extends TypeInterface
{
    /**
     * All properties of this type that are currently readable. May depend on authorizations of the accessing user.
     *
     * A restricted view on the properties of the {@link TypeInterface::getEntityClass() backing object}. Potentially
     * mapped via {@link AliasableTypeInterface::getAliases() aliases}.
     *
     * Implementations must return the nested arrays with keys that does not conflict with each other.
     *
     * You can easily merge the contained arrays via `array_merge(...$type->getReadableProperties())`.
     *
     * @return array{0: array<non-empty-string, AttributeReadability<TEntity>>, 1: array<non-empty-string, ToOneRelationshipReadability<TCondition, TSorting, TEntity, object, TransferableTypeInterface<TCondition, TSorting, object>>>, 2: array<non-empty-string, ToManyRelationshipReadability<TCondition, TSorting, TEntity, object, TransferableTypeInterface<TCondition, TSorting, object>>>}
     */
    public function getReadableProperties(): array;

    /**
     * @return array{0: array<non-empty-string, Updatability<TCondition>>, 1: array<non-empty-string, RelationshipUpdatability<TCondition, object>>, 2: array<non-empty-string, RelationshipUpdatability<TCondition, object>>}
     */
    public function getUpdatableProperties(): array;
}
