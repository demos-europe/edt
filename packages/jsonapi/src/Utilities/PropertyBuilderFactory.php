<?php

declare(strict_types=1);

namespace EDT\JsonApi\Utilities;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilder;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilder;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use Webmozart\Assert\Assert;
use function is_string;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class PropertyBuilderFactory
{
    public function __construct(
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver
    ) {}

    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $entityClass
     * @param PropertyPathInterface|non-empty-string $name
     *
     * @return AttributeConfigBuilder<TCondition, TEntity>
     *
     * @throws PathException
     */
    public function createAttribute(string $entityClass, PropertyPathInterface|string $name): AttributeConfigBuilder
    {
        return new AttributeConfigBuilder(
            $this->getSingleName($name),
            $entityClass,
            $this->propertyAccessor,
            $this->typeResolver
        );
    }

    /**
     * @template TEntity of object
     * @template TRelationship of object
     *
     * @param class-string<TEntity> $entityClass
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface<TCondition, TSorting, TRelationship> $nameAndType
     *
     * @return ToOneRelationshipConfigBuilder<TCondition, TSorting, TEntity, TRelationship>
     *
     * @throws PathException
     */
    public function createToOne(
        string $entityClass,
        PropertyPathInterface&ResourceTypeInterface $nameAndType
    ): ToOneRelationshipConfigBuilder {
        $builder = new ToOneRelationshipConfigBuilder(
            $entityClass,
            $nameAndType->getEntityClass(),
            $this->propertyAccessor,
            $this->getSingleName($nameAndType)
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
     * @return ToOneRelationshipConfigBuilder<TCondition, TSorting, TEntity, TRelationship>
     *
     * @throws PathException
     */
    public function createToOneWithType(
        string $entityClass,
        string $relationshipClass,
        PropertyPathInterface|string $name
    ): ToOneRelationshipConfigBuilder {
        return new ToOneRelationshipConfigBuilder(
            $entityClass,
            $relationshipClass,
            $this->propertyAccessor,
            $this->getSingleName($name)
        );
    }

    /**
     * @template TEntity of object
     * @template TRelationship of object
     *
     * @param class-string<TEntity> $entityClass
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface<TCondition, TSorting, TRelationship> $nameAndType
     *
     * @return ToManyRelationshipConfigBuilder<TCondition, TSorting, TEntity, TRelationship>
     *
     * @throws PathException
     */
    public function createToMany(
        string $entityClass,
        PropertyPathInterface&ResourceTypeInterface $nameAndType
    ): ToManyRelationshipConfigBuilder {
        $builder = new ToManyRelationshipConfigBuilder(
            $entityClass,
            $nameAndType->getEntityClass(),
            $this->propertyAccessor,
            $this->getSingleName($nameAndType)
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
     * @return ToManyRelationshipConfigBuilder<TCondition, TSorting, TEntity, TRelationship>
     *
     * @throws PathException
     */
    public function createToManyWithType(
        string $entityClass,
        string $relationshipClass,
        PropertyPathInterface|string $nameOrPath
    ): ToManyRelationshipConfigBuilder {
        return new ToManyRelationshipConfigBuilder(
            $entityClass,
            $relationshipClass,
            $this->propertyAccessor,
            $this->getSingleName($nameOrPath)
        );
    }

    /**
     * @param PropertyPathInterface|non-empty-string $nameOrPath
     * @return non-empty-string
     *
     * @throws PathException
     */
    protected function getSingleName(PropertyPathInterface|string $nameOrPath)
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
        return new IdentifierConfigBuilder($entityClass, $this->propertyAccessor, $this->typeResolver);
    }
}
