<?php

declare(strict_types=1);

namespace EDT\Wrapping\PropertyBehavior\Relationship\ToMany;

use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\RelationshipInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\Contracts\Types\RelationshipFetchableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\CreationDataInterface;
use EDT\Wrapping\PropertyBehavior\AbstractConstructorBehavior;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdaterTrait;
use function array_key_exists;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * @template-implements RelationshipInterface<NamedTypeInterface&RelationshipFetchableTypeInterface<TCondition, TSorting, object>>
 */
class ToManyRelationshipConstructorBehavior extends AbstractConstructorBehavior implements RelationshipInterface
{
    use PropertyUpdaterTrait;

    /**
     * @template TRelationship of object
     *
     * @param non-empty-string $argumentName
     * @param non-empty-string $propertyName
     * @param TransferableTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     * @param list<TCondition> $relationshipConditions
     * @param null|callable(CreationDataInterface): array{mixed, list<non-empty-string>} $customBehavior
     */
    public function __construct(
        string $propertyName,
        string $argumentName,
        protected readonly TransferableTypeInterface $relationshipType,
        protected readonly array $relationshipConditions,
        ?callable $customBehavior,
        OptionalField $optional
    ) {
        parent::__construct($propertyName, $argumentName, $optional, $customBehavior);
    }

    /**
     * @param non-empty-string|null $argumentName will fall back to the property name if `null`
     * @param list<TCondition> $relationshipConditions
     * @param null|callable(CreationDataInterface): array{mixed, list<non-empty-string>} $customBehavior
     *
     * @return callable(non-empty-string, non-empty-list<non-empty-string>, class-string, ResourceTypeInterface<TCondition, PathsBasedInterface, object>): ConstructorBehaviorInterface
     */
    public static function createFactory(
        ?string $argumentName,
        array $relationshipConditions,
        mixed $customBehavior,
        OptionalField $optional
    ) {
        return new class($argumentName, $relationshipConditions, $customBehavior, $optional) {
            /**
             * @param non-empty-string|null $argumentName will fall back to the property name if `null`
             * @param list<TCondition> $relationshipConditions
             * @param null|callable(CreationDataInterface): array{mixed, list<non-empty-string>} $customBehavior
             */
            public function __construct(
                protected readonly ?string $argumentName,
                protected readonly array $relationshipConditions,
                protected readonly mixed $customBehavior,
                protected readonly OptionalField $optional
            ) {}

            /**
             * @param non-empty-string $name
             * @param non-empty-list<non-empty-string> $propertyPath
             * @param class-string $entityClass
             * @param ResourceTypeInterface<TCondition, PathsBasedInterface, object> $relationshipType
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface $relationshipType): ConstructorBehaviorInterface
            {
                return new ToManyRelationshipConstructorBehavior(
                    $name,
                    $this->argumentName ?? $name,
                    $relationshipType,
                    $this->relationshipConditions,
                    $this->customBehavior,
                    $this->optional
                );
            }
        };
    }

    public function getRelationshipType(): object
    {
        return $this->relationshipType;
    }

    protected function isValueInRequest(CreationDataInterface $entityData): bool
    {
        return array_key_exists($this->resourcePropertyName, $entityData->getToManyRelationships());
    }

    protected function getArgumentValueFromRequest(CreationDataInterface $entityData): mixed
    {
        return $this->determineToManyRelationshipValues(
            $this->getRelationshipType(),
            $this->relationshipConditions,
            $entityData->getToManyRelationships()[$this->resourcePropertyName]
        );
    }

    public function getRequiredToManyRelationships(): array
    {
        return array_fill_keys($this->getRequiredPropertyList(), $this->relationshipType->getTypeName());
    }

    public function getOptionalToManyRelationships(): array
    {
        return array_fill_keys($this->getOptionalPropertyList(), $this->relationshipType->getTypeName());
    }
}
