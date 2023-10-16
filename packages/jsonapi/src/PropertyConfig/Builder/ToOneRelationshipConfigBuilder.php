<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\PropertyConfig\DtoToOneRelationshipConfig;
use EDT\JsonApi\PropertyConfig\ToOneRelationshipConfigInterface;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\CallbackToOneRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\Factory\CallbackToOneRelationshipSetBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\Factory\PathToOneRelationshipSetBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\Factory\ToOneRelationshipConstructorBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\PathToOneRelationshipReadability;
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
     * @var list<RelationshipSetBehaviorFactoryInterface<TCondition, TSorting, TEntity, TRelationship>>
     */
    protected array $updateBehaviorFactories = [];

    /**
     * @var list<RelationshipConstructorBehaviorFactoryInterface<TCondition>>
     */
    protected array $constructorBehaviorFactories = [];

    /**
     * @var list<RelationshipSetBehaviorFactoryInterface<TCondition, TSorting, TEntity, TRelationship>>
     */
    protected array $postConstructorBehaviorFactories = [];

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
     * @return $this
     */
    public function creatable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null,
        array $relationshipConditions = []
    ): self {
        if ($constructorArgument) {
            $this->addConstructorBehavior(
                new ToOneRelationshipConstructorBehaviorFactory(
                    $customConstructorArgumentName,
                    $relationshipConditions,
                    null
                )
            );
        }

        $this->addPostConstructorBehavior(null === $postConstructorCallback
            ? new PathToOneRelationshipSetBehaviorFactory($relationshipConditions, $optionalAfterConstructor, $this->propertyAccessor, [])
            : new CallbackToOneRelationshipSetBehaviorFactory($postConstructorCallback, $relationshipConditions, $optionalAfterConstructor, [])
        );

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

    public function updatable(array $entityConditions = [], array $relationshipConditions = [], callable $updateCallback = null): ToOneRelationshipConfigBuilderInterface
    {
        return $this->addUpdateBehavior(null === $updateCallback
            ? new PathToOneRelationshipSetBehaviorFactory($relationshipConditions, true, $this->propertyAccessor, $entityConditions)
            : new CallbackToOneRelationshipSetBehaviorFactory($updateCallback, $relationshipConditions, true, $entityConditions)
        );
    }

    public function build(): ToOneRelationshipConfigInterface
    {
        if (null === $this->relationshipType) {
            throw new InvalidArgumentException('The relationship type must be set before a config can be build.');
        }

        $postConstructorBehaviors = array_map(fn (
            RelationshipSetBehaviorFactoryInterface $factory
        ): RelationshipSetBehaviorInterface => $factory->createSetBehavior(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass,
            $this->relationshipType
        ), $this->postConstructorBehaviorFactories);

        $constructorBehaviors = array_map(fn (
            RelationshipConstructorBehaviorFactoryInterface $factory
        ): ConstructorBehaviorInterface => $factory->createRelationshipConstructorBehavior(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass,
            $this->relationshipType
        ), $this->constructorBehaviorFactories);

        $updateBehaviors = array_map(fn (
            RelationshipSetBehaviorFactoryInterface $factory
        ): RelationshipSetBehaviorInterface => $factory->createSetBehavior(
            $this->name,
            $this->getPropertyPath(),
            $this->entityClass,
            $this->relationshipType
        ), $this->updateBehaviorFactories);


        return new DtoToOneRelationshipConfig(
            ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $this->relationshipType),
            $updateBehaviors,
            $postConstructorBehaviors,
            $constructorBehaviors,
            $this->getFilterLink($this->relationshipType),
            $this->getSortLink($this->relationshipType)
        );
    }

    /**
     * @return $this
     */
    public function addConstructorBehavior(RelationshipConstructorBehaviorFactoryInterface $behaviorFactory): ToOneRelationshipConfigBuilderInterface
    {
        $this->constructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    /**
     * @return $this
     */
    public function addPostConstructorBehavior(RelationshipSetBehaviorFactoryInterface $behaviorFactory): ToOneRelationshipConfigBuilderInterface
    {
        $this->postConstructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    /**
     * @return $this
     */
    public function addUpdateBehavior(RelationshipSetBehaviorFactoryInterface $behaviorFactory): ToOneRelationshipConfigBuilderInterface
    {
        $this->updateBehaviorFactories[] = $behaviorFactory;

        return $this;
    }
}
