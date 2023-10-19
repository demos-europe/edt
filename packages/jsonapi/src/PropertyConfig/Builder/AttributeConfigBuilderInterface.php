<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityFactoryInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends ReadablePropertyConfigBuilderInterface<TEntity, simple_primitive|array<int|string, mixed>|null>
 */
interface AttributeConfigBuilderInterface extends PropertyConfigBuilderInterface, ReadablePropertyConfigBuilderInterface
{
    /**
     * @return $this
     */
    public function addConstructorBehavior(ConstructorBehaviorFactoryInterface $behaviorFactory): self;

    /**
     * @param PropertyUpdatabilityFactoryInterface<TCondition> $behaviorFactory
     *
     * @return $this
     */
    public function addPostConstructorBehavior(PropertyUpdatabilityFactoryInterface $behaviorFactory): self;

    /**
     * @param PropertyUpdatabilityFactoryInterface<TCondition> $behaviorFactory
     *
     * @return $this
     */
    public function addUpdateBehavior(PropertyUpdatabilityFactoryInterface $behaviorFactory): self;

    /**
     * @param list<TCondition> $entityConditions
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $updateCallback
     *
     * @return $this
     */
    public function updatable(array $entityConditions = [], callable $updateCallback = null): self;


    /**
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $postConstructorCallback
     * @param non-empty-string|null $customConstructorArgumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     *
     * @return $this
     */
    public function creatable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null
    ): self;
}
