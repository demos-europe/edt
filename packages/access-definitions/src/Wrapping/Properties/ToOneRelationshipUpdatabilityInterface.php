<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use Exception;

/**
 * Provides updatability information and behavior for a to-many relationship property.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipUpdatabilityInterface<TCondition, TransferableTypeInterface<TCondition, TSorting, TRelationship>>
 */
interface ToOneRelationshipUpdatabilityInterface extends RelationshipUpdatabilityInterface
{
    /**
     * Update the relationship property this instance corresponds to by replacing the value in the
     * given entity with the given relationship entity.
     *
     * The implementation must be able to handle the given value (i.e. transform it into a valid
     * format to be stored in the attribute) or throw an exception.
     *
     * @param TEntity $entity
     * @param TRelationship|null $relationship
     *
     * @throws Exception
     */
    public function updateToOneRelationship(object $entity, ?object $relationship): void;
}
