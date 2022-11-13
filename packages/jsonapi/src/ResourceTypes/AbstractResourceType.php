<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\OutputTransformation\DynamicTransformerFactory;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\JsonApi\OutputTransformation\DynamicTransformer;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use InvalidArgumentException;
use League\Fractal\TransformerAbstract;
use Psr\Log\LoggerInterface;

/**
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\SortMethodInterface
 * @template TEntity of object
 *
 * @template-implements ResourceTypeInterface<TCondition, TSorting, TEntity>
 */
abstract class AbstractResourceType implements ResourceTypeInterface
{
    public function getReadableProperties(): array
    {
        return array_map(
            fn (Property $property): ?TypeInterface => $property instanceof Relationship
                ? $this->getTypeProvider()
                    ->requestType($property->getTypeIdentifier())
                    ->instanceOf(TransferableTypeInterface::class)
                    ->exposedAsRelationship()
                    ->getInstanceOrThrow()
                : null,
            $this->getPropertyCollection()->getReadableProperties()
        );
    }

    public function getFilterableProperties(): array
    {
        return array_map(
            fn (Property $property): ?TypeInterface => $property instanceof Relationship
                ? $this->getTypeProvider()
                    ->requestType($property->getTypeIdentifier())
                    ->instanceOf(FilterableTypeInterface::class)
                    ->exposedAsRelationship()
                    ->getInstanceOrThrow()
                : null,
            $this->getPropertyCollection()->getFilterableProperties()
        );
    }

    public function getSortableProperties(): array
    {
        return array_map(
            fn (Property $property): ?TypeInterface => $property instanceof Relationship
                ? $this->getTypeProvider()
                    ->requestType($property->getTypeIdentifier())
                    ->instanceOf(SortableTypeInterface::class)
                    ->exposedAsRelationship()
                    ->getInstanceOrThrow()
                : null,
            $this->getPropertyCollection()->getSortableProperties()
        );
    }

    public function getUpdatableProperties(object $updateTarget): array
    {
        return [];
    }

    /**
     * @return array<non-empty-string, list<TCondition>>
     *
     * @see CreatableTypeInterface::getInitializableProperties()
     */
    public function getInitializableProperties(): array
    {
        $properties = $this->getPropertyCollection()->getInitializableProperties();

        return array_map(
            // TODO: implement support for initialization conditions
            static fn (Property $property): array => [],
            $properties
        );
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
        return (new DynamicTransformerFactory($this->getMessageFormatter(), $this->getLogger()))
            ->createTransformer($this, $this->getWrapperFactory());
    }

    /**
     * Array order: Even though the order of the properties returned within the array may have an
     * effect (e.g. determining the order of properties in JSON:API responses) you can not rely on
     * these effects; they may be changed in the future.
     *
     * @return list<PropertyBuilder>
     */
    abstract protected function getProperties(): array;

    abstract protected function getWrapperFactory(): WrapperObjectFactory;

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
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface $path
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
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface $path
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
     * @return TypeProviderInterface<TCondition, TSorting>
     */
    abstract protected function getTypeProvider(): TypeProviderInterface;

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
        $properties = array_map(
            static fn (PropertyBuilder $propertyBuilder): Property => $propertyBuilder->build(),
            $properties
        );

        return new PropertyCollection($properties);
    }
}
