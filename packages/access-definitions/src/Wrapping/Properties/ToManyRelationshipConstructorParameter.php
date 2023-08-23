<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * @template-extends AbstractRelationshipConstructorParameter<TCondition, TSorting>
 */
class ToManyRelationshipConstructorParameter extends AbstractRelationshipConstructorParameter
{
    use PropertyUpdaterTrait;

    /**
     * @return list<object>
     */
    public function getArgument(?string $entityId, EntityDataInterface $entityData): array
    {
        $toManyRelationships = $entityData->getToManyRelationships();
        $relationshipRefs = $toManyRelationships[$this->propertyName]
            ?? throw new \InvalidArgumentException("No to-many relationship '$this->propertyName' present.");

        return $this->determineToManyRelationshipValues(
            $this->getRelationshipType(),
            $this->getRelationshipConditions(),
            $relationshipRefs
        );
    }

    public function getRequiredToManyRelationships(): array
    {
        return [$this->propertyName => $this->relationshipType->getTypeName()];
    }
}
