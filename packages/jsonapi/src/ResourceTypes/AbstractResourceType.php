<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\OutputTransformation\DynamicTransformerFactory;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\ReadableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\JsonApi\OutputTransformation\DynamicTransformer;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Utilities\TypeAccessor;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use InvalidArgumentException;
use League\Fractal\TransformerAbstract;
use Psr\Log\LoggerInterface;
use function array_key_exists;

/**
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\SortMethodInterface
 * @template TEntity of object
 *
 * @template-implements ResourceTypeInterface<TCondition, TSorting, TEntity>
 */
abstract class AbstractResourceType implements ResourceTypeInterface
{
    /**
     * @return array<non-empty-string, non-empty-string|null>
     *
     * @see ReadableTypeInterface::getReadableProperties()
     */
    public function getReadableProperties(): array
    {
        $properties = $this->getPropertyCollection()->getReadableProperties();

        return $this->getTypeIdentifiersOrNull($properties);
    }

    /**
     * @return array<non-empty-string, non-empty-string|null>
     *
     * @see FilterableTypeInterface::getFilterableProperties()
     */
    public function getFilterableProperties(): array
    {
        $properties = $this->getPropertyCollection()->getFilterableProperties();

        return $this->getTypeIdentifiersOrNull($properties);
    }

    /**
     * @return array<non-empty-string, non-empty-string|null>
     *
     * @see SortableTypeInterface::getSortableProperties()
     */
    public function getSortableProperties(): array
    {
        $properties = $this->getPropertyCollection()->getSortableProperties();

        return $this->getTypeIdentifiersOrNull($properties);
    }

    /**
     * @return array<non-empty-string, list<TCondition>>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    public function getInitializableProperties(): array
    {
        $properties = $this->getPropertyCollection()->getInitializableProperties();

        return array_map(static function (Property $property): array {
            // TODO: implement support for initialization conditions
            return [];
        }, $properties);
    }

    /**
     * @return list<non-empty-string>
     *
     * @see CreatableTypeInterface::getPropertiesRequiredForCreation()
     */
    public function getPropertiesRequiredForCreation(): array
    {
        return $this->getPropertyCollection()->getPropertyNamesRequiredForCreation();
    }

    public function getAliases(): array
    {
        return $this->getPropertyCollection()->getAliasPaths();
    }

    /**
     * @return DynamicTransformer<TEntity>
     */
    public function getTransformer(): TransformerAbstract
    {
        return (new DynamicTransformerFactory(
            $this->getTypeAccessor(),
            $this->getMessageFormatter(),
            $this->getLogger()
        ))->createTransformer($this, $this->getWrapperFactory());
    }

    /**
     * Relationships: Relationships returned by this method will only have any effect, if they are
     * {@link TypeInterface::isDirectlyAccessible() accessible} and
     * {@link TypeInterface::isReferencable() referencable}.
     *
     * Array order: Even though the order of the properties returned within the array may have an
     * effect (e.g. determining the order of properties in JSON:API responses) you can not rely on
     * these effects; they may be changed in the future.
     *
     * @return list<PropertyBuilder>
     */
    abstract protected function getProperties(): array;

    abstract protected function getWrapperFactory(): WrapperObjectFactory;

    /**
     * @return TypeAccessor<TCondition, TSorting>
     */
    abstract protected function getTypeAccessor(): TypeAccessor;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getMessageFormatter(): MessageFormatter;

    /**
     * @param list<PropertyBuilder<TEntity, mixed>> $properties
     *
     * @return list<PropertyBuilder<TEntity, mixed>>
     */
    protected function processProperties(array $properties): array
    {
        return $properties;
    }

    /**
     * @return PropertyBuilder<TEntity, mixed>
     */
    protected function createAttribute(PropertyPathInterface $path): PropertyBuilder
    {
        return new PropertyBuilder($path, $this->getEntityClass());
    }

    /**
     * @template TRelationship of object
     *
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship> $path
     *
     * @return RelationshipBuilder<TEntity, TRelationship>
     */
    protected function createToOneRelationship(
        PropertyPathInterface $path,
        bool $defaultInclude = false
    ): RelationshipBuilder {
        return new RelationshipBuilder($path, $this->getEntityClass(), $defaultInclude);
    }

    /**
     * @template TRelationship of object
     *
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship> $path
     *
     * @return RelationshipBuilder<TEntity, TRelationship>
     */
    protected function createToManyRelationship(
        PropertyPathInterface $path,
        bool $defaultInclude = false
    ): RelationshipBuilder {
        return new RelationshipBuilder($path, $this->getEntityClass(), $defaultInclude);
    }

    /**
     * @return PropertyCollection<TEntity>
     *
     * @throws InvalidArgumentException
     *
     * @see PropertyBuilder::readable()
     */
    public function getPropertyCollection(): PropertyCollection
    {
        $properties = $this->getProperties();
        $properties = $this->processProperties($properties);
        $properties = array_map(static function (PropertyBuilder $propertyBuilder): Property {
            return $propertyBuilder->build();
        }, $properties);

        return new PropertyCollection($properties);
    }

    /**
     * @param array<non-empty-string, Property<TEntity, mixed>> $properties
     *
     * @return array<non-empty-string, non-empty-string|null>
     */
    private function getTypeIdentifiersOrNull(array $properties): array
    {
        $internalProperties = $this->getInternalProperties();

        return array_map(function (Property $property) use ($internalProperties): ?string {
            $propertyName = $property->getName();
            if (!array_key_exists($propertyName, $internalProperties)) {
                throw new InvalidArgumentException("Property `$propertyName` was not configured in resource type `{$this::getName()}`.");
            }

            return $internalProperties[$propertyName];
        }, $properties);
    }
}
