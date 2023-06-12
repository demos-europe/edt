<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

/**
 * Provides updatability information and behavior for a relationship (i.e. a non-attribute) property.
 *
 * @template TCondition of PathsBasedInterface
 * @template TRelationshipType of TransferableTypeInterface
 *
 * @template-extends PropertyAccessibilityInterface<TCondition>
 */
interface RelationshipUpdatabilityInterface extends PropertyUpdatabilityInterface, PropertyAccessibilityInterface
{
    /**
     * Get the type definition of the relationship property represented by this instance.
     *
     * Currently, all relationships in to-many relationship properties must have the same type
     * definition. Polymorphic to-many relationships are not supported yet.
     *
     * @return TRelationshipType
     */
    public function getRelationshipType(): TransferableTypeInterface;

    /**
     * Updates of the relationship property corresponding to this instance shall be denied if
     * any of the relationship entities to be set does not match any conditions in the returned list.
     *
     * @return list<TCondition>
     */
    public function getRelationshipConditions(): array;
}
