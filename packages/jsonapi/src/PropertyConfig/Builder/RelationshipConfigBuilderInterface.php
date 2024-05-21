<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\JsonApi\ApiDocumentation\DefaultField;
use EDT\JsonApi\ApiDocumentation\DefaultInclude;
use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Contracts\ResourceTypeProviderInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipSetBehaviorFactoryInterface;

/**
 * This interface define configuration options that are available to-one and to-many relationships.
 *
 * @template TEntity of object
 * @template TRelationship of object
 * @template TValue of list<TRelationship>|TRelationship|null the value of the property
 *
 * @template-extends AttributeOrRelationshipBuilderInterface<TEntity, TValue, RelationshipConstructorBehaviorFactoryInterface, RelationshipSetBehaviorFactoryInterface<TEntity, TRelationship>, RelationshipSetBehaviorFactoryInterface<TEntity, TRelationship>>
 */
interface RelationshipConfigBuilderInterface extends AttributeOrRelationshipBuilderInterface
{
    /**
     * @param ResourceTypeInterface<TRelationship>|ResourceTypeProviderInterface<TRelationship> $relationshipType $relationshipType
     *
     * @return $this
     */
    public function setRelationshipType(ResourceTypeInterface|ResourceTypeProviderInterface $relationshipType): self;

    public function setReadableByPath(
        DefaultField $defaultField = DefaultField::NO,
        DefaultInclude $defaultInclude = DefaultInclude::NO
    ): self;

    /**
     * @param callable(TEntity): TValue $behavior
     *
     * @return $this
     */
    public function setReadableByCallable(
        callable $behavior,
        DefaultField $defaultField = DefaultField::NO,
        DefaultInclude $defaultInclude = DefaultInclude::NO
    ): self;

    /**
     * @param bool $defaultField see {@link DefaultField}
     * @param null|callable(TEntity): TValue $customReadCallback to be set if this property needs special handling when read
     * @param bool $defaultInclude see {@link DefaultInclude}
     *
     * @return $this
     *
     * @deprecated use {@link setReadableByPath()} or {@link setReadableByCallable()} instead
     */
    public function readable(
        bool $defaultField = false,
        callable $customReadCallback = null,
        bool $defaultInclude = false
    ): self;

    /**
     * @param list<DrupalFilterInterface> $entityConditions
     * @param list<DrupalFilterInterface> $relationshipConditions
     * @param null|callable(TEntity, TValue): list<non-empty-string> $updateCallback
     *
     * @return $this
     *
     * @deprecated use {@link addPathUpdateBehavior()} or {@link addUpdateBehavior()} instead.
     */
    public function updatable(array $entityConditions = [], array $relationshipConditions = [], callable $updateCallback = null): self;

    /**
     * @param null|callable(TEntity, TValue): list<non-empty-string> $postConstructorCallback
     * @param non-empty-string|null $customConstructorArgumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     * @param list<DrupalFilterInterface> $relationshipConditions
     *
     * @return $this
     *
     * @depercated use any of the other `add*CreationBehavior` methods instead.
     */
    public function initializable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null,
        array $relationshipConditions = []
    ): self;

    /**
     * @param list<DrupalFilterInterface> $entityConditions
     * @param list<DrupalFilterInterface> $relationshipConditions
     *
     * @return $this
     */
    public function addPathUpdateBehavior(array $entityConditions = [], array $relationshipConditions = []): self;

    /**
     * @param list<DrupalFilterInterface> $entityConditions
     * @param list<DrupalFilterInterface> $relationshipConditions
     */
    public function addPathCreationBehavior(OptionalField $optional = OptionalField::NO, array $entityConditions = [], array $relationshipConditions = []): self;
}
