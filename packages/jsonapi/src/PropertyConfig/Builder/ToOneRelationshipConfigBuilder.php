<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\PropertyConfig\DtoToOneRelationshipConfig;
use EDT\JsonApi\PropertyConfig\ToOneRelationshipConfigInterface;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\CallbackToOneRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\CallbackToOneRelationshipSetability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\PathToOneRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\PathToOneRelationshipSetability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipConstructorParameter;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipReadabilityInterface;
use InvalidArgumentException;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilder<TCondition, TSorting, TRelationship>
 * @template-implements ToOneRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, TRelationship>
 * @template-implements BuildableInterface<ToOneRelationshipConfigInterface<TCondition, TSorting, TEntity, TRelationship>>
 */
class ToOneRelationshipConfigBuilder
    extends RelationshipConfigBuilder
    implements ToOneRelationshipConfigBuilderInterface, BuildableInterface
{
    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>
     */
    protected $updatabilityFactory;
    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): ToOneRelationshipConstructorParameter<TCondition, TSorting>
     */
    protected $instantiabilityFactory;

    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>
     */
    protected $postInstantiabilityFactory;

    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>
     */
    protected $readabilityFactory;

    /**
     * @param class-string<TEntity> $entityClass
     * @param class-string<TRelationship> $relationshipClass
     * @param non-empty-string $name
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly string $relationshipClass,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        string $name
    ) {
        parent::__construct($name);
    }

    /**
     * @param null|callable(TEntity, TRelationship|null): bool $postInstantiationCallback
     * @param non-empty-string|null $argumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     * @param list<TCondition> $relationshipConditions
     *
     * @return $this
     */
    public function instantiable(
        bool $optional = false,
        callable $postInstantiationCallback = null,
        bool $argument = false,
        ?string $argumentName = null,
        array $relationshipConditions = []
    ): self {
        if ($argument) {
            $this->instantiabilityFactory = new class (
                $argumentName,
                $relationshipConditions
            ) {
                /**
                 * @param non-empty-string|null $argumentName
                 * @param list<TCondition> $relationshipConditions
                 */
                public function __construct(
                    protected readonly ?string $argumentName,
                    protected readonly array $relationshipConditions
                ) {}

                /**
                 * @param non-empty-string $name
                 * @param non-empty-list<non-empty-string> $propertyPath
                 * @param class-string<TEntity> $entityClass
                 * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
                 *
                 * @return ToOneRelationshipConstructorParameter<TCondition, TSorting>
                 */
                public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface $relationshipType): ToOneRelationshipConstructorParameter
                {
                    return new ToOneRelationshipConstructorParameter(
                        $this->argumentName ?? $name,
                        $name,
                        $relationshipType,
                        $this->relationshipConditions
                    );
                }
            };
        }

        $this->postInstantiabilityFactory = new class (
            $postInstantiationCallback,
            $relationshipConditions,
            $optional,
            $this->propertyAccessor,
        ) {
            /**
             * @var null|callable(TEntity, TRelationship|null): bool
             */
            private $postInstantiationCallback;

            /**
             * @param null|callable(TEntity, TRelationship|null): bool $postInstantiationCallback
             * @param list<TCondition> $relationshipConditions
             */
            public function __construct(
                ?callable $postInstantiationCallback,
                protected readonly array $relationshipConditions,
                protected readonly bool $optional,
                protected readonly PropertyAccessorInterface $propertyAccessor,
            ) {
                $this->postInstantiationCallback = $postInstantiationCallback;
            }

            /**
             * @param non-empty-string $name
             * @param non-empty-list<non-empty-string> $propertyPath
             * @param class-string<TEntity> $entityClass
             * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
             *
             * @return RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface $relationshipType): RelationshipSetabilityInterface
            {
                return null === $this->postInstantiationCallback
                    ? new PathToOneRelationshipSetability(
                        $name,
                        $entityClass,
                        [],
                        $this->relationshipConditions,
                        $relationshipType,
                        $propertyPath,
                        $this->propertyAccessor,
                        $this->optional
                    )
                    : new CallbackToOneRelationshipSetability(
                        $name,
                        [],
                        $this->relationshipConditions,
                        $relationshipType,
                        $this->postInstantiationCallback,
                        $this->optional
                    );
            }
        };

        return $this;
    }

    /**
     * @param bool $defaultField the field is to be returned in responses by default
     * @param null|callable(TEntity): (TRelationship|null) $customReadCallback to be set if this property needs special handling when read
     *
     * @return $this
     */
    public function readable(
        bool $defaultField = false,
        callable $customReadCallback = null,
        bool $defaultInclude = false
    ): self {
        $this->readabilityFactory = new class (
            $this->propertyAccessor,
            $defaultField,
            $defaultInclude,
            $customReadCallback
        ) {
            /**
             * @var null|callable(TEntity): (TRelationship|null)
             */
            private $customReadCallback;

            /**
             * @param null|callable(TEntity): (TRelationship|null) $customReadCallback
             */
            public function __construct(
                protected readonly PropertyAccessorInterface $propertyAccessor,
                protected readonly bool $defaultField,
                protected readonly bool $defaultInclude,
                ?callable $customReadCallback
            ) {
                $this->customReadCallback = $customReadCallback;
            }

            /**
             * @param non-empty-string $name
             * @param non-empty-list<non-empty-string> $propertyPath
             * @param class-string<TEntity> $entityClass
             * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
             *
             * @return ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface $relationshipType): ToOneRelationshipReadabilityInterface
            {
                return null === $this->customReadCallback
                    ? new PathToOneRelationshipReadability(
                        $entityClass,
                        $propertyPath,
                        $this->defaultField,
                        $this->defaultInclude,
                        $relationshipType,
                        $this->propertyAccessor
                    ) : new CallbackToOneRelationshipReadability(
                        $this->defaultField,
                        $this->defaultInclude,
                        $this->customReadCallback,
                        $relationshipType
                    );
            }
        };

        return $this;
    }

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param null|callable(TEntity, TRelationship|null): bool $updateCallback
     *
     * @return $this
     */
    public function updatable(array $entityConditions = [], array $relationshipConditions = [], callable $updateCallback = null): self
    {
        $this->updatabilityFactory = new class(
            $this->propertyAccessor,
            $entityConditions,
            $relationshipConditions,
            $updateCallback
        ) {
            /**
             * @var null|callable(TEntity, TRelationship|null): bool
             */
            private $updateCallback;

            /**
             * @param list<TCondition> $entityConditions
             * @param list<TCondition> $relationshipConditions
             * @param null|callable(TEntity, TRelationship|null): bool $updateCallback
             */
            public function __construct(
                protected readonly PropertyAccessorInterface $propertyAccessor,
                protected readonly array $entityConditions,
                protected readonly array $relationshipConditions,
                ?callable $updateCallback
            ) {
                $this->updateCallback = $updateCallback;
            }

            /**
             * @param non-empty-string $name
             * @param non-empty-list<non-empty-string> $propertyPath
             * @param class-string<TEntity> $entityClass
             * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
             *
             * @return RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface $relationshipType): RelationshipSetabilityInterface
            {
                return  null === $this->updateCallback
                    ? new PathToOneRelationshipSetability(
                        $name,
                        $entityClass,
                        $this->entityConditions,
                        $this->relationshipConditions,
                        $relationshipType,
                        $propertyPath,
                        $this->propertyAccessor,
                        true
                    )
                    : new CallbackToOneRelationshipSetability(
                        $name,
                        $this->entityConditions,
                        $this->relationshipConditions,
                        $relationshipType,
                        $this->updateCallback,
                        true
                    );
            }
        };

        return $this;
    }


    public function build(): ToOneRelationshipConfigInterface
    {
        if (null === $this->relationshipType) {
            throw new InvalidArgumentException('The relationship type must be set before a config can be build.');
        }

        return new DtoToOneRelationshipConfig(
            ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            ($this->updatabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            ($this->postInstantiabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            ($this->instantiabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            $this->getFilterLink($this->relationshipType),
            $this->getSortLink($this->relationshipType)
        );
    }
}
