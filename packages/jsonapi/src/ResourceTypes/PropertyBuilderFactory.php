<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\Querying\Contracts\EntityBasedInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\PropertyPathInterface;

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
     * @param PropertyPathInterface $path
     *
     * @return PropertyBuilder<TEntity, mixed, TCondition, TSorting>
     *
     * @throws PathException
     */
    public function createAttribute(string $entityClass, PropertyPathInterface $path): PropertyBuilder
    {
        return new PropertyBuilder(
            $path,
            $entityClass,
            null,
            $this->propertyAccessor,
            $this->typeResolver
        );
    }

    /**
     * @template TEntity of object
     * @template TRelationship of object
     *
     * @param class-string<TEntity> $entityClass
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface<TCondition, TSorting, TRelationship> $path
     *
     * @return PropertyBuilder<TEntity, TRelationship, TCondition, TSorting>
     *
     * @throws PathException
     */
    public function createToOne(
        string $entityClass,
        PropertyPathInterface&ResourceTypeInterface $path,
        bool $defaultInclude
    ): PropertyBuilder {
        return new PropertyBuilder(
            $path,
            $entityClass,
            [
                'relationshipType' => $path,
                'defaultInclude' => $defaultInclude,
                'toMany' => false,
            ],
            $this->propertyAccessor,
            $this->typeResolver
        );
    }

    /**
     * @template TEntity of object
     * @template TRelationship of object
     *
     * @param class-string<TEntity> $entityClass
     * @param PropertyPathInterface&EntityBasedInterface<TRelationship>&ResourceTypeInterface<TCondition, TSorting, TRelationship> $path
     *
     * @return PropertyBuilder<TEntity, TRelationship, TCondition, TSorting>
     *
     * @throws PathException
     */
    public function createToMany(
        string $entityClass,
        PropertyPathInterface&ResourceTypeInterface $path,
        bool $defaultInclude = false
    ): PropertyBuilder {
        return new PropertyBuilder(
            $path,
            $entityClass,
            [
                'relationshipType' => $path,
                'defaultInclude' => $defaultInclude,
                'toMany' => true,
            ],
            $this->propertyAccessor,
            $this->typeResolver
        );
    }
}
