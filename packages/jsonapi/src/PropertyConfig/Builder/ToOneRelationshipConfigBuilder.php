<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\JsonApi\PropertyConfig\DtoToOneRelationshipConfig;
use EDT\JsonApi\PropertyConfig\ToOneRelationshipConfigInterface;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Contracts\ResourceTypeProviderInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\CallbackToOneRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\CallbackToOneRelationshipSetBehavior;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\PathToOneRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\PathToOneRelationshipSetBehavior;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipConstructorBehavior;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipReadabilityInterface;

/**
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilder<TEntity, TRelationship, TRelationship|null, ToOneRelationshipReadabilityInterface<TEntity, TRelationship>>
 * @template-implements ToOneRelationshipConfigBuilderInterface<TEntity, TRelationship>
 * @template-implements BuildableInterface<ToOneRelationshipConfigInterface<TEntity, TRelationship>>
 */
class ToOneRelationshipConfigBuilder
    extends RelationshipConfigBuilder
    implements ToOneRelationshipConfigBuilderInterface, BuildableInterface
{
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

    public function initializable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null,
        array $relationshipConditions = []
    ): self {
        if ($constructorArgument) {
            $this->addConstructorBehavior(
                ToOneRelationshipConstructorBehavior::createFactory(
                    $customConstructorArgumentName,
                    $relationshipConditions,
                    null,
                    OptionalField::NO
                )
            );
        }

        $optional = OptionalField::fromBoolean($optionalAfterConstructor);

        return null === $postConstructorCallback
            ? $this->addPathCreationBehavior($optional)
            : $this->addCreationBehavior(
                CallbackToOneRelationshipSetBehavior::createFactory($postConstructorCallback, $relationshipConditions, $optional, [])
            );
    }

    public function addPathCreationBehavior(OptionalField $optional = OptionalField::NO, array $entityConditions = [], array $relationshipConditions = []): self
    {
        return $this->addCreationBehavior(
            PathToOneRelationshipSetBehavior::createFactory($relationshipConditions, $optional, $this->propertyAccessor, $entityConditions)
        );
    }

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
             * @param ResourceTypeInterface<TRelationship>|ResourceTypeProviderInterface<TRelationship> $relationshipType
             *
             * @return ToOneRelationshipReadabilityInterface<TEntity, TRelationship>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface|ResourceTypeProviderInterface $relationshipType): ToOneRelationshipReadabilityInterface
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
            ? PathToOneRelationshipSetBehavior::createFactory($relationshipConditions, OptionalField::YES, $this->propertyAccessor, $entityConditions)
            : CallbackToOneRelationshipSetBehavior::createFactory($updateCallback, $relationshipConditions, OptionalField::YES, $entityConditions)
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
}
