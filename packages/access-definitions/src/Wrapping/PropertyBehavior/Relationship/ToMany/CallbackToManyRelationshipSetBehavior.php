<?php

declare(strict_types=1);

namespace EDT\Wrapping\PropertyBehavior\Relationship\ToMany;

use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\TransferableConfigProviderInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\Factory\CallbackToManyRelationshipSetBehaviorFactory;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends AbstractToManyRelationshipSetBehavior<TCondition, TSorting, TEntity, TRelationship>
 */
class CallbackToManyRelationshipSetBehavior extends AbstractToManyRelationshipSetBehavior
{
    /**
     * @param non-empty-string $propertyName the exposed property name accepted by this instance
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship>|TransferableConfigProviderInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param callable(TEntity, list<TRelationship>): list<non-empty-string> $setterCallback
     */
    public function __construct(
        string $propertyName,
        array $entityConditions,
        array $relationshipConditions,
        TransferableTypeInterface|TransferableConfigProviderInterface $relationshipType,
        protected readonly mixed $setterCallback,
        OptionalField $optional = OptionalField::NO
    ) {
        parent::__construct($propertyName, $entityConditions, $relationshipConditions, $optional, $relationshipType);
    }

    /**
     * @template TCond of PathsBasedInterface
     * @template TEnt of object
     * @template TRel of object
     *
     * @param callable(TEnt, list<TRel>): list<non-empty-string> $setBehaviorCallback
     * @param list<TCond> $relationshipConditions
     * @param list<TCond> $entityConditions
     *
     * @return RelationshipSetBehaviorFactoryInterface<TCond, PathsBasedInterface, TEnt, TRel>
     */
    public static function createFactory(
        callable $setBehaviorCallback,
        array $relationshipConditions,
        OptionalField $optional,
        array $entityConditions
    ): RelationshipSetBehaviorFactoryInterface {
        return new CallbackToManyRelationshipSetBehaviorFactory($setBehaviorCallback, $relationshipConditions, $optional, $entityConditions);
    }

    public function updateToManyRelationship(object $entity, array $relationships): array
    {
        return ($this->setterCallback)($entity, $relationships);
    }

    public function getDescription(): string
    {
        $relationshipType = $this->getRelationshipType()->getTypeName();

        return ($this->optional->equals(OptionalField::YES)
                ? "Allows a to-many relationship `$this->propertyName` with the type `$relationshipType` to be present in the request body, but does not require it. "
                : "Requires a to-many relationship `$this->propertyName` with the type `$relationshipType` to be present in the request body.")
            . 'If the property is present in the request body it will be passed to a callback, which is able to adjust the target entity or execute side effects.'
            . ([] === $this->entityConditions
                ? 'The target entity does not need to '
                : 'The target entity must ')
            . 'match additional conditions beside the ones defined by its type.'
            . ([] === $this->relationshipConditions
                ? 'The relationships do not need to '
                : 'The relationships must ')
            . 'match additional conditions beside the ones defined by their type.';
    }
}
