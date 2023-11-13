<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\PropertyConfig\DtoToManyRelationshipConfig;
use EDT\JsonApi\PropertyConfig\ToManyRelationshipConfigInterface;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\CallbackToManyRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\Factory\CallbackToManyRelationshipSetBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\Factory\PathToManyRelationshipSetBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\Factory\ToManyRelationshipConstructorBehaviorFactory;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\PathToManyRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;
use InvalidArgumentException;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilder<TCondition, TSorting, TEntity, TRelationship>
 * @template-implements ToManyRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, TRelationship>
 * @template-implements BuildableInterface<ToManyRelationshipConfigInterface<TCondition, TSorting, TEntity, TRelationship>>
 */
class ToManyRelationshipConfigBuilder
    extends RelationshipConfigBuilder
    implements ToManyRelationshipConfigBuilderInterface, BuildableInterface
{
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
        string $entityClass,
        protected readonly string $relationshipClass,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        string $name
    ) {
        parent::__construct($entityClass, $name);
    }

    /**
     * @return $this
     */
    public function initializable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null,
        array $relationshipConditions = []
    ): self {
        if ($constructorArgument) {
            $this->addConstructorBehavior(
                new ToManyRelationshipConstructorBehaviorFactory(
                    $customConstructorArgumentName,
                    $relationshipConditions,
                    null
                )
            );
        }

        $this->addPostConstructorBehavior(null === $postConstructorCallback
            ? new PathToManyRelationshipSetBehaviorFactory($relationshipConditions, $optionalAfterConstructor, $this->propertyAccessor, [])
            : new CallbackToManyRelationshipSetBehaviorFactory($postConstructorCallback, $relationshipConditions, $optionalAfterConstructor, [])
        );

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
     * @return $this
     */
    public function updatable(array $entityConditions = [], array $relationshipConditions = [], callable $updateCallback = null): self
    {
        return $this->addUpdateBehavior(null === $updateCallback
            ? new PathToManyRelationshipSetBehaviorFactory($relationshipConditions, true, $this->propertyAccessor, $entityConditions)
            : new CallbackToManyRelationshipSetBehaviorFactory($updateCallback, $relationshipConditions, true, $entityConditions)
        );
    }

    public function build(): ToManyRelationshipConfigInterface
    {
        $relationshipType = $this->getFinalRelationshipType();

        $postConstructorBehaviors = $this->getPostConstructorBehaviors();
        $constructorBehaviors = $this->getConstructorBehaviors();
        $updateBehaviors = $this->getUpdateBehaviors();

        return new DtoToManyRelationshipConfig(
            ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $relationshipType),
            $updateBehaviors,
            $postConstructorBehaviors,
            $constructorBehaviors,
            $this->getFilterLink($relationshipType),
            $this->getSortLink($relationshipType)
        );
    }

    /**
     * @return $this
     */
    public function addConstructorBehavior(RelationshipConstructorBehaviorFactoryInterface $behaviorFactory): ToManyRelationshipConfigBuilderInterface
    {
        $this->constructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    /**
     * @return $this
     */
    public function addPostConstructorBehavior(RelationshipSetBehaviorFactoryInterface $behaviorFactory): ToManyRelationshipConfigBuilderInterface
    {
        $this->postConstructorBehaviorFactories[] = $behaviorFactory;

        return $this;
    }

    /**
     * @return $this
     */
    public function addUpdateBehavior(RelationshipSetBehaviorFactoryInterface $behaviorFactory): ToManyRelationshipConfigBuilderInterface
    {
        $this->updateBehaviorFactories[] = $behaviorFactory;

        return $this;
    }
}
