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

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilder<TCondition, TSorting, TEntity, TRelationship>
 * @template-implements ToOneRelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, TRelationship>
 * @template-implements BuildableInterface<ToOneRelationshipConfigInterface<TCondition, TSorting, TEntity, TRelationship>>
 */
class ToOneRelationshipConfigBuilder
    extends RelationshipConfigBuilder
    implements ToOneRelationshipConfigBuilderInterface, BuildableInterface
{
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
        $relationshipType = $this->getFinalRelationshipType();
        $readability = $this->getReadability($relationshipType);
        $postConstructorBehaviors = $this->getPostConstructorBehaviors();
        $constructorBehaviors = $this->getConstructorBehaviors();
        $updateBehaviors = $this->getUpdateBehaviors();
        $filterLink = $this->getFilterLink($relationshipType);
        $sortLink = $this->getSortLink($relationshipType);

        return new DtoToOneRelationshipConfig(
            $readability,
            $updateBehaviors,
            $postConstructorBehaviors,
            $constructorBehaviors,
            $filterLink,
            $sortLink
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

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TRelationship> $relationshipType
     *
     * @return ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, TRelationship>|null
     */
    protected function getReadability($relationshipType): ?ToOneRelationshipReadabilityInterface
    {
        return ($this->readabilityFactory ?? static fn () => null)($this->name, $this->getPropertyPath(), $this->entityClass, $relationshipType);
    }
}
