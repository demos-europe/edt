<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;
use EDT\Wrapping\Properties\AttributeUpdatabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipUpdatabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipUpdatabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends IdentifiableTypeInterface<TCondition, TSorting, TEntity>
 */
interface TransferableTypeInterface extends IdentifiableTypeInterface
{
    /**
     * Shall return all properties of this instance that are currently readable.
     *
     * The return may depend on the current state of the application and thus may change on consecutive calls.
     *
     * Implementations must return the nested arrays with keys that do not conflict with each other.
     *
     * Hint: You can merge the returned nested arrays via `array_merge(...$type->getReadableProperties())`.
     *
     * @return array{0: array<non-empty-string, AttributeReadabilityInterface<TEntity>>, 1: array<non-empty-string, ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, object>>, 2: array<non-empty-string, ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, object>>}
     */
    public function getReadableProperties(): array;

    /**
     * Shall return all properties of this instance that are currently updatable.
     *
     * The return may depend on the current state of the application and thus may change on consecutive calls.
     *
     * Implementations must return the nested arrays with keys that do not conflict with each other.
     *
     * Hint: You can merge the returned nested arrays via `array_merge(...$type->getUpdatableProperties())`.
     *
     * @return array{0: array<non-empty-string, AttributeUpdatabilityInterface<TCondition, TEntity>>, 1: array<non-empty-string, ToOneRelationshipUpdatabilityInterface<TCondition, TSorting, TEntity, object>>, 2: array<non-empty-string, ToManyRelationshipUpdatabilityInterface<TCondition, TSorting, TEntity, object>>}
     */
    public function getUpdatableProperties(): array;

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string;
}
