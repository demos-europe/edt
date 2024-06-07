<?php

declare(strict_types=1);

namespace EDT\JsonApi\Utilities;

use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilder;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use Webmozart\Assert\Assert;
use function is_string;
use EDT\Wrapping\Utilities\AttributeTypeResolverInterface;

/**
 * Allows to create instances to configure properties (id/attribute/relationship). Each instance created can be used to
 * configure the single property it was created for.
 */
class PropertyBuilderFactory
{
    /**
     * By default, a whitelist approach is encouraged. I.e. a property builder created by this class will not expose
     * any behavior. However, if needed, you can enable default behaviors for attributes, to-one relationships or
     * to-many relationships. If behavior is enabled, the resource property name must match a property in the
     * corresponding entity and no value transformations will be done.
     */
    public function __construct(
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolverInterface $typeResolver,
        protected readonly bool $identifierByDefaultSortable = false,
        protected readonly bool $identifierByDefaultFilterable = false,
        protected readonly bool $attributesByDefaultSortable = false,
        protected readonly bool $attributesByDefaultFilterable = false,
        protected readonly bool $attributesByDefaultReadable = false,
        protected readonly bool $toOneRelationshipsByDefaultSortable = false,
        protected readonly bool $toOneRelationshipsByDefaultFilterable = false,
        protected readonly bool $toOneRelationshipsByDefaultReadable = false,
        protected readonly bool $toManyRelationshipsByDefaultSortable = false,
        protected readonly bool $toManyRelationshipsByDefaultFilterable = false,
        protected readonly bool $toManyRelationshipsByDefaultReadable = false
    ) {}

    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $entityClass
     * @param PropertyPathInterface|non-empty-string $name
     *
     * @return AttributeConfigBuilder<TEntity>
     *
     * @throws PathException
     */
    public function createAttribute(string $entityClass, PropertyPathInterface|string $name): AttributeConfigBuilder
    {
        $builder = new AttributeConfigBuilder(
            $this->getSingleName($name),
            $entityClass,
            $this->propertyAccessor,
            $this->typeResolver
        );

        if ($this->attributesByDefaultSortable) {
            $builder->setSortable();
        }
        if ($this->attributesByDefaultFilterable) {
            $builder->setFilterable();
        }
        if ($this->attributesByDefaultReadable) {
            $builder->setReadableByPath();
        }
        
        return $builder;
    }

    /**
     * @template TEntity of object
     * @template TRelationship of object
     *
     * @param class-string<TEntity> $entityClass
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface<TRelationship> $nameAndType
     *
     * @return ToOneRelationshipConfigBuilder<TEntity, TRelationship>
     *
     * @throws PathException
     */
    public function createToOne(
        string $entityClass,
        PropertyPathInterface&ResourceTypeInterface $nameAndType
    ): ToOneRelationshipConfigBuilder {
        $builder = $this->createToOneWithType(
            $entityClass,
            $nameAndType->getEntityClass(),
            $nameAndType
        );

        $builder->setRelationshipType($nameAndType);

        return $builder;
    }

    /**
     * @template TEntity of object
     * @template TRelationship of object
     *
     * @param class-string<TEntity> $entityClass
     * @param class-string<TRelationship> $relationshipClass
     * @param PropertyPathInterface|non-empty-string $name
     *
     * @return ToOneRelationshipConfigBuilder<TEntity, TRelationship>
     *
     * @throws PathException
     */
    public function createToOneWithType(
        string $entityClass,
        string $relationshipClass,
        PropertyPathInterface|string $name
    ): ToOneRelationshipConfigBuilder {
        $builder = new ToOneRelationshipConfigBuilder(
            $entityClass,
            $relationshipClass,
            $this->propertyAccessor,
            $this->getSingleName($name)
        );

        if ($this->toOneRelationshipsByDefaultSortable) {
            $builder->setSortable();
        }
        if ($this->toOneRelationshipsByDefaultFilterable) {
            $builder->setFilterable();
        }
        if ($this->toOneRelationshipsByDefaultReadable) {
            $builder->setReadableByPath();
        }

        return $builder;
    }

    /**
     * @template TEntity of object
     * @template TRelationship of object
     *
     * @param class-string<TEntity> $entityClass
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface<TRelationship> $nameAndType
     *
     * @return ToManyRelationshipConfigBuilder<TEntity, TRelationship>
     *
     * @throws PathException
     */
    public function createToMany(
        string $entityClass,
        PropertyPathInterface&ResourceTypeInterface $nameAndType
    ): ToManyRelationshipConfigBuilder {
        $builder = $this->createToManyWithType(
            $entityClass,
            $nameAndType->getEntityClass(),
            $nameAndType
        );

        $builder->setRelationshipType($nameAndType);

        return $builder;
    }

    /**
     * @template TEntity of object
     * @template TRelationship of object
     *
     * @param class-string<TEntity> $entityClass
     * @param class-string<TRelationship> $relationshipClass
     * @param PropertyPathInterface|non-empty-string $nameOrPath
     *
     * @return ToManyRelationshipConfigBuilder<TEntity, TRelationship>
     *
     * @throws PathException
     */
    public function createToManyWithType(
        string $entityClass,
        string $relationshipClass,
        PropertyPathInterface|string $nameOrPath
    ): ToManyRelationshipConfigBuilder {
        $builder = new ToManyRelationshipConfigBuilder(
            $entityClass,
            $relationshipClass,
            $this->propertyAccessor,
            $this->getSingleName($nameOrPath)
        );

        if ($this->toManyRelationshipsByDefaultSortable) {
            $builder->setSortable();
        }
        if ($this->toManyRelationshipsByDefaultFilterable) {
            $builder->setFilterable();
        }
        if ($this->toManyRelationshipsByDefaultReadable) {
            $builder->setReadableByPath();
        }

        return $builder;
    }

    /**
     * @param PropertyPathInterface|non-empty-string $nameOrPath
     * @return non-empty-string
     *
     * @throws PathException
     */
    protected function getSingleName(PropertyPathInterface|string $nameOrPath): string
    {
        if (!is_string($nameOrPath)) {
            $pathNames = $nameOrPath->getAsNames();
            Assert::count($pathNames, 1);

            return array_pop($pathNames);
        }

        return $nameOrPath;
    }

    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $entityClass
     *
     * @return IdentifierConfigBuilder<TEntity>
     */
    public function createIdentifier(string $entityClass): IdentifierConfigBuilder
    {
        $builder = new IdentifierConfigBuilder($entityClass, $this->propertyAccessor, $this->typeResolver);

        if ($this->identifierByDefaultSortable) {
            $builder->setSortable();
        }
        if ($this->identifierByDefaultFilterable) {
            $builder->setFilterable();
        }

        return $builder;
    }
}
