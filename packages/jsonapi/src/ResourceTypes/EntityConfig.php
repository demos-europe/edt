<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\Properties\EntityInitializability;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLink;
use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyUpdatableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;
use EDT\Wrapping\Properties\ConstructorParameterInterface;
use EDT\Wrapping\Properties\EntityReadability;
use EDT\Wrapping\Properties\EntityUpdatability;
use EDT\Wrapping\Properties\IdReadabilityInterface;
use EDT\Wrapping\Properties\PropertySetabilityInterface;
use EDT\Wrapping\Properties\RelationshipSetabilityInterface;
use EDT\Wrapping\Properties\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipReadabilityInterface;
use ReflectionClass;
use ReflectionMethod;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements PropertyUpdatableTypeInterface<TCondition, TSorting, TEntity>
 */
class EntityConfig implements PropertyUpdatableTypeInterface
{
    /**
     * @param class-string<TEntity> $entityClass
     * @param array<non-empty-string, PropertyConfig<TEntity, mixed, TCondition, TSorting>> $propertyConfigs
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly array $propertyConfigs
    ) {}

    /**
     * @return EntityInitializability<TEntity, TCondition, TSorting>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    public function getInitializability(): EntityInitializability
    {
        $reflectionClass = new ReflectionClass($this->entityClass);
        $constructor = $this->getConstructor($reflectionClass);
        $reflectionConstructorParameter = $constructor?->getParameters() ?? [];

        $constructorParameterConfigs = array_values(Iterables::removeNull(array_map(
            static fn (PropertyConfig $property): ?ConstructorParameterInterface => $property->getInitializingConstructorParameter(),
            $this->propertyConfigs
        )));

        $setabilities = array_values(Iterables::removeNull(array_map(
            static fn (PropertyConfig $property): ?PropertySetabilityInterface => $property->getInitializingSetability(),
            $this->propertyConfigs
        )));

        return new EntityInitializability($this->entityClass, $reflectionConstructorParameter, $constructorParameterConfigs, $setabilities);
    }

    /**
     * @return EntityUpdatability<TCondition, TSorting, TEntity>
     */
    public function getUpdatability(): EntityUpdatability
    {
        return new EntityUpdatability(
            Iterables::removeNull(array_map(
                static fn (PropertyConfig $property): ?PropertySetabilityInterface => $property->getAttributeUpdatingSetability(),
                $this->propertyConfigs
            )),
            Iterables::removeNull(array_map(
                static fn (PropertyConfig $property): ?RelationshipSetabilityInterface => $property->getToOneRelationshipUpdatingSetabilities(),
                $this->propertyConfigs
            )),
            Iterables::removeNull(array_map(
                static fn (PropertyConfig $property): ?RelationshipSetabilityInterface => $property->getToManyRelationshipUpdatingSetability(),
                $this->propertyConfigs
            ))
        );
    }

    /**
     * @return EntityReadability<TCondition, TSorting, TEntity>
     */
    public function getReadability(): EntityReadability
    {
        $idReadabilities = Iterables::removeNull(array_map(
            static fn (PropertyConfig $property): ?IdReadabilityInterface => $property->getIdentifierReadability(),
            $this->propertyConfigs
        ));
        Assert::count($idReadabilities, 1);

        return new EntityReadability(
            Iterables::removeNull(array_map(
                static fn (PropertyConfig $property): ?AttributeReadabilityInterface => $property->getAttributeReadability(),
                $this->propertyConfigs
            )),
            Iterables::removeNull(
                array_map(
                    static fn (PropertyConfig $property): ?ToOneRelationshipReadabilityInterface => $property->getToOneRelationshipReadability(),
                    $this->propertyConfigs
                )
            ),
            Iterables::removeNull(
                array_map(
                    static fn (PropertyConfig $property): ?ToManyRelationshipReadabilityInterface => $property->getToManyRelationshipReadability(),
                    $this->propertyConfigs
                )
            ),
            array_pop($idReadabilities)
        );
    }

    /**
     * @return array<non-empty-string, PropertyLink<SortingTypeInterface<TCondition, TSorting>>> The keys in the returned array are the names of the properties.
     */
    public function getSortingProperties(): array
    {
        $propertyArray = array_map(
            static fn (PropertyConfig $property): PropertyLink => new PropertyLink(
                $property->getPropertyPath(),
                $property->getSortableRelationshipType()
            ),
            array_filter(
                $this->propertyConfigs,
                static fn (PropertyConfig $property): bool => $property->isSortable()
            )
        );

        return $this->keepExposedTypes($propertyArray);
    }

    /**
     * @return array<non-empty-string, PropertyLink<FilteringTypeInterface<TCondition, TSorting>>> The keys in the returned array are the names of the properties.
     */
    public function getFilteringProperties(): array
    {
        $propertyArray = array_map(
            static fn (PropertyConfig $property): PropertyLink => new PropertyLink(
                $property->getPropertyPath(),
                $property->getFilterableRelationshipType()
            ),
            array_filter(
                $this->propertyConfigs,
                static fn (PropertyConfig $property): bool => $property->isFilterable()
            )
        );

        return $this->keepExposedTypes($propertyArray);
    }

    /**
     * @param ReflectionClass<object> $class
     */
    protected function getConstructor(ReflectionClass $class): ?ReflectionMethod
    {
        $constructor = $class->getConstructor();
        if (null === $constructor) {
            $parent = $class->getParentClass();
            if (false === $parent) {
                return null;
            }
            return $this->getConstructor($parent);
        }

        return $constructor;
    }

    /**
     * Even if a relationship property was defined in this type, we do not allow its usage if the
     * target type of the relationship is not set as exposed.
     *
     * @template TType of object
     *
     * @param array<non-empty-string, PropertyLink<TType>> $types
     *
     * @return array<non-empty-string, PropertyLink<TType>>
     */
    protected function keepExposedTypes(array $types): array
    {
        return array_filter(
            $types,
            static function (PropertyLink $property): bool {
                $type = $property->getTargetType();
                return null === $type
                    || ($type instanceof ExposableRelationshipTypeInterface
                        && $type->isExposedAsRelationship());
            }
        );
    }
}
