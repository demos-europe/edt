<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Relationships;

use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AbstractSetability;
use EDT\Wrapping\Properties\EntityDataInterface;
use EDT\Wrapping\Properties\RelationshipSetabilityInterface;
use Exception;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-implements RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>
 * @template-extends AbstractSetability<TCondition, TEntity>
 */
abstract class AbstractToOneRelationshipSetability extends AbstractSetability implements RelationshipSetabilityInterface
{
    use PropertyUpdaterTrait;

    /**
     * @param non-empty-string $propertyName
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     */
    public function __construct(
        string $propertyName,
        array $entityConditions,
        protected readonly array $relationshipConditions,
        bool $optional
    ) {
        parent::__construct($propertyName, $entityConditions, $optional);
    }

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
     * @return bool `true` if the update had side effects, i.e. it changed properties other than
     *              the one this instance corresponds to; `false` otherwise
     *
     * @throws Exception
     */
    abstract protected function updateToOneRelationship(object $entity, ?object $relationship): bool;

    public function updateProperty(object $entity, EntityDataInterface $entityData): bool
    {
        $requestRelationships = $entityData->getToOneRelationships();
        $relationshipReference = $requestRelationships[$this->propertyName];
        $relationshipValue = $this->determineToOneRelationshipValue(
            $this->getRelationshipType(),
            $this->relationshipConditions,
            $relationshipReference
        );

        return $this->updateToOneRelationship($entity, $relationshipValue);
    }

    public function getRequiredAttributes(): array
    {
        return [];
    }

    public function getOptionalAttributes(): array
    {
        return [];
    }

    public function getRequiredToOneRelationships(): array
    {
        return $this->optional
            ? []
            : [$this->propertyName => $this->getRelationshipType()->getTypeName()];
    }

    public function getOptionalToOneRelationships(): array
    {
        return $this->optional
            ? [$this->propertyName => $this->getRelationshipType()->getTypeName()]
            : [];
    }

    public function getRequiredToManyRelationships(): array
    {
        return [];
    }

    public function getOptionalToManyRelationships(): array
    {
        return [];
    }
}
