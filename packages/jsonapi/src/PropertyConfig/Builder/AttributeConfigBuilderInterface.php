<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityFactoryInterface;

/**
 * This interface define configuration options that are only available for resource attributes.
 *
 * Besides that, its type itself can be used to denote a resource attribute.
 *
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AttributeOrRelationshipBuilderInterface<TEntity, TCondition, simple_primitive|array<int|string, mixed>|null, ConstructorBehaviorFactoryInterface, PropertyUpdatabilityFactoryInterface<TCondition, TEntity>, PropertyUpdatabilityFactoryInterface<TCondition, TEntity>>
 */
interface AttributeConfigBuilderInterface extends AttributeOrRelationshipBuilderInterface
{
    /**
     * @param list<TCondition> $entityConditions
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): list<non-empty-string> $updateCallback
     *
     * @return $this
     *
     * @deprecated use {@link addPathUpdateBehavior} or {@link addUpdateBehavior} instead
     */
    public function updatable(array $entityConditions = [], callable $updateCallback = null): self;

    /**
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): list<non-empty-string> $postConstructorCallback
     * @param non-empty-string|null $customConstructorArgumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     *
     * @return $this
     *
     * @deprecated use {@link addConstructorBehavior}, {@link addCreationBehavior} or {@link addPathCreationBehavior} instead
     */
    public function initializable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null
    ): self;
}
