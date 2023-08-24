<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\JsonApi\Requests\PropertyUpdaterTrait;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

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
     * @param non-empty-string $argumentName
     * @param non-empty-string $propertyName
     * @param TransferableTypeInterface<TCondition, TSorting, object> $relationshipType
     * @param list<TCondition> $relationshipConditions
     */
    public function __construct(
        string $argumentName,
        string $propertyName,
        TransferableTypeInterface $relationshipType,
        protected readonly array $relationshipConditions
    ) {
        parent::__construct($argumentName, $propertyName, $relationshipType);
    }

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
            $this->relationshipConditions,
            $relationshipRefs
        );
    }

    public function getRequiredToManyRelationships(): array
    {
        return [$this->propertyName => $this->relationshipType->getTypeName()];
    }
}
