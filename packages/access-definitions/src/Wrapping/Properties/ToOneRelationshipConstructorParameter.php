<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * @template-extends AbstractRelationshipConstructorParameter<TCondition, TSorting>
 */
class ToOneRelationshipConstructorParameter extends AbstractRelationshipConstructorParameter
{
    use PropertyUpdaterTrait;

    public function getArgument(?string $entityId, EntityDataInterface $entityData): ?object
    {
        $toOneRelationships = $entityData->getToOneRelationships();
        $relationshipRef = $toOneRelationships[$this->propertyName]
            ?? throw new \InvalidArgumentException("No to-one relationship '$this->propertyName' present.");

        return $this->determineToOneRelationshipValue(
            $this->getRelationshipType(),
            $this->getRelationshipConditions(),
            $relationshipRef
        );
    }
    
    public function getRequiredToOneRelationships(): array
    {
        return [$this->propertyName => $this->relationshipType->getTypeName()];
    }
}
