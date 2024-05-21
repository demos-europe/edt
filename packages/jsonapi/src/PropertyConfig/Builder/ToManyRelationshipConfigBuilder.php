<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\JsonApi\PropertyConfig\DtoToManyRelationshipConfig;
use EDT\JsonApi\PropertyConfig\ToManyRelationshipConfigInterface;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Contracts\ResourceTypeProviderInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\CallbackToManyRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\CallbackToManyRelationshipSetBehavior;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\PathToManyRelationshipReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\PathToManyRelationshipSetBehavior;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipConstructorBehavior;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;

/**
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends RelationshipConfigBuilder<TEntity, TRelationship, list<TRelationship>, ToManyRelationshipReadabilityInterface<TEntity, TRelationship>>
 * @template-implements ToManyRelationshipConfigBuilderInterface<TEntity, TRelationship>
 * @template-implements BuildableInterface<ToManyRelationshipConfigInterface<TEntity, TRelationship>>
 */
class ToManyRelationshipConfigBuilder
    extends RelationshipConfigBuilder
    implements ToManyRelationshipConfigBuilderInterface, BuildableInterface
{
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

    public function initializable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null,
        array $relationshipConditions = []
    ): self {
        if ($constructorArgument) {
            $this->addConstructorBehavior(
                ToManyRelationshipConstructorBehavior::createFactory(
                    $customConstructorArgumentName,
                    $relationshipConditions,
                    null,
                    OptionalField::NO
                )
            );
        }

        $optional = OptionalField::fromBoolean($optionalAfterConstructor);

        return null === $postConstructorCallback
            ? $this->addPathCreationBehavior($optional, [], $relationshipConditions)
            : $this->addCreationBehavior(
                CallbackToManyRelationshipSetBehavior::createFactory($postConstructorCallback, $relationshipConditions, $optional, [])
            );
    }

    public function addPathCreationBehavior(OptionalField $optional = OptionalField::NO, array $entityConditions = [], array $relationshipConditions = []): self
    {
        return $this->addCreationBehavior(
            PathToManyRelationshipSetBehavior::createFactory($relationshipConditions, $optional, $this->propertyAccessor, $entityConditions)
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
             * @param ResourceTypeInterface<TRelationship>|ResourceTypeProviderInterface<TRelationship> $relationshipType
             *
             * @return ToManyRelationshipReadabilityInterface<TEntity, TRelationship>
             */
            public function __invoke(string $name, array $propertyPath, string $entityClass, ResourceTypeInterface|ResourceTypeProviderInterface $relationshipType): ToManyRelationshipReadabilityInterface
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

    public function updatable(array $entityConditions = [], array $relationshipConditions = [], callable $updateCallback = null): self
    {
        return $this->addUpdateBehavior(null === $updateCallback
            ? PathToManyRelationshipSetBehavior::createFactory($relationshipConditions, OptionalField::YES, $this->propertyAccessor, $entityConditions)
            : CallbackToManyRelationshipSetBehavior::createFactory($updateCallback, $relationshipConditions, OptionalField::YES, $entityConditions)
        );
    }

    public function build(): ToManyRelationshipConfigInterface
    {
        $relationshipType = $this->getFinalRelationshipType();
        $readability = $this->getReadability($relationshipType);
        $postConstructorBehaviors = $this->getPostConstructorBehaviors();
        $constructorBehaviors = $this->getConstructorBehaviors();
        $updateBehaviors = $this->getUpdateBehaviors();
        $filterLink = $this->getFilterLink($relationshipType);
        $sortLink = $this->getSortLink($relationshipType);

        return new DtoToManyRelationshipConfig(
            $readability,
            $updateBehaviors,
            $postConstructorBehaviors,
            $constructorBehaviors,
            $filterLink,
            $sortLink
        );
    }
}
