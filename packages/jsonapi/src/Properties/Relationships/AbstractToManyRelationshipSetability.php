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
abstract class AbstractToManyRelationshipSetability extends AbstractSetability implements RelationshipSetabilityInterface
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
     * Update the relationship property this instance corresponds to by replacing the list in the
     * given entity with the given list of relationship entities.
     *
     * The implementation must be able to handle the given relationship value (i.e. transform it
     * into a valid format to be stored in the attribute) or throw an exception.
     *
     * @param TEntity $entity
     * @param list<TRelationship> $relationships
     *
     * @return bool `true` if the update had side effects, i.e. it changed properties other than
     *              the one this instance corresponds to; `false` otherwise
     *
     * @throws Exception
     */
    abstract protected function updateToManyRelationship(object $entity, array $relationships): bool;

    public function updateProperty(object $entity, EntityDataInterface $entityData): bool
    {
        $requestRelationships = $entityData->getToManyRelationships();
        $relationshipReferences = $requestRelationships[$this->propertyName];
        $relationshipValues = $this->determineToManyRelationshipValues(
            $this->getRelationshipType(),
            $this->relationshipConditions,
            $relationshipReferences
        );

        return $this->updateToManyRelationship($entity, $relationshipValues);
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
        return [];
    }

    public function getOptionalToOneRelationships(): array
    {
        return [];
    }

    public function getRequiredToManyRelationships(): array
    {
        return $this->optional
            ? []
            : [$this->propertyName => $this->getRelationshipType()->getTypeName()];
    }

    public function getOptionalToManyRelationships(): array
    {
        return $this->optional
            ? [$this->propertyName => $this->getRelationshipType()->getTypeName()]
            : [];
    }
}
