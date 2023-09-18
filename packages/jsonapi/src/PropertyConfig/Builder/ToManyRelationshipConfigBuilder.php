<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\PropertyConfig\DtoToManyRelationshipConfig;
use EDT\JsonApi\PropertyConfig\ToManyRelationshipConfigInterface;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\CallbackToManyRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\CallbackToManyRelationshipSetability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\PathToManyRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\PathToManyRelationshipSetability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipConstructorParameter;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;
use InvalidArgumentException;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilder<TCondition, TSorting, TRelationship>
 * @template-implements ToManyRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, TRelationship>
 * @template-implements BuildableInterface<ToManyRelationshipConfigInterface<TCondition, TSorting, TEntity, TRelationship>>
 */
class ToManyRelationshipConfigBuilder
    extends RelationshipConfigBuilder
    implements ToManyRelationshipConfigBuilderInterface, BuildableInterface
{
    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>
     */
    protected $updatabilityFactory;
    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): ToManyRelationshipConstructorParameter<TCondition, TSorting>
     */
    protected $instantiabilityFactory;

    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): RelationshipSetabilityInterface<TCondition, TSorting, TEntity, TRelationship>
     */
    protected $postInstantiabilityFactory;

    /**
     * @var null|callable(non-empty-string, non-empty-list<non-empty-string>, class-string<TEntity>, ResourceTypeInterface<TCondition, TSorting, TRelationship>): ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>
     */
    protected $readabilityFactory;

    /**
     * @param class-string<TEntity> $entityClass
     * @param class-string<TRelationship> $relationshipClass,
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
     * @param null|callable(TEntity, list<TRelationship>): bool $postInstantiationCallback
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
                 * @return ToManyRelationshipConstructorParameter<TCondition, TSorting>
                 */
                public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface $relationshipType): ToManyRelationshipConstructorParameter
                {
                    return new ToManyRelationshipConstructorParameter(
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
             * @var null|callable(TEntity, list<TRelationship>): bool
             */
            private $postInstantiationCallback;

            /**
             * @param null|callable(TEntity, list<TRelationship>): bool $postInstantiationCallback
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
                    ? new PathToManyRelationshipSetability(
                        $name,
                        $entityClass,
                        [],
                        $this->relationshipConditions,
                        $relationshipType,
                        $propertyPath,
                        $this->propertyAccessor,
                        $this->optional
                    )
                    : new CallbackToManyRelationshipSetability(
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
     * @param null|callable(TEntity): list<TRelationship> $customReadCallback to be set if this property needs special handling when read
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
             * @var null|callable(TEntity): list<TRelationship>
             */
            private $customReadCallback;

            /**
             * @param null|callable(TEntity): list<TRelationship> $customReadCallback
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
             * @return ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface $relationshipType): ToManyRelationshipReadabilityInterface
            {
                return null === $this->customReadCallback
                    ? new PathToManyRelationshipReadability(
                        $entityClass,
                        $propertyPath,
                        $this->defaultField,
                        $this->defaultInclude,
                        $relationshipType,
                        $this->propertyAccessor
                    ) : new CallbackToManyRelationshipReadability(
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
     * @param null|callable(TEntity, list<TRelationship>): bool $updateCallback
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
             * @var null|callable(TEntity, list<TRelationship>): bool
             */
            private $updateCallback;

            /**
             * @param list<TCondition> $entityConditions
             * @param list<TCondition> $relationshipConditions
             * @param null|callable(TEntity, list<TRelationship>): bool $updateCallback
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
                    ? new PathToManyRelationshipSetability(
                        $name,
                        $entityClass,
                        $this->entityConditions,
                        $this->relationshipConditions,
                        $relationshipType,
                        $propertyPath,
                        $this->propertyAccessor,
                        true
                    )
                    : new CallbackToManyRelationshipSetability(
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


    public function build(): ToManyRelationshipConfigInterface
    {
        if (null === $this->relationshipType) {
            throw new InvalidArgumentException('The relationship type must be set before a config can be build.');
        }

        return new DtoToManyRelationshipConfig(
            ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            ($this->updatabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            ($this->postInstantiabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            ($this->instantiabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            $this->getFilterLink($this->relationshipType),
            $this->getSortLink($this->relationshipType)
        );
    }
}
