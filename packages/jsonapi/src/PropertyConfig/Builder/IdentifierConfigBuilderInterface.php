<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\Factory\IdentifierPostConstructorBehaviorFactoryInterface;

/**
 * This interface define configuration options that are only available for the resource ID.
 *
 * Besides that, it can be used to denote a to-one relationship property.
 *
 * @template TEntity of object
 * @template TCondition of PathsBasedInterface
 *
 * @template-extends PropertyConfigBuilderInterface<TEntity, TCondition, non-empty-string>
 */
interface IdentifierConfigBuilderInterface extends PropertyConfigBuilderInterface
{
    /**
     * @param null|callable(TEntity): non-empty-string $customReadCallback to be set if this property needs special handling when read
     *
     * @return $this
     *
     * @deprecated use {@link setReadableByPath} or {@link setReadableByCallable} instead
     */
    public function readable(callable $customReadCallback = null): self;

    /**
     * @param callable(non-empty-list<non-empty-string>, class-string): ConstructorBehaviorInterface $behaviorFactory
     *
     * @return $this
     */
    public function addConstructorCreationBehavior(callable $behaviorFactory): self;

    /**
     * @return $this
     */
    public function addCreationBehavior(IdentifierPostConstructorBehaviorFactoryInterface $behaviorFactory): self;

    /**
     * @param non-empty-string|null $customConstructorArgumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     *
     * @return $this
     *
     * @deprecated use any of the `add*CreationBehavior` methods instead
     */
    public function initializable(
        bool $optionalAfterConstructor = false,
        bool $constructorArgument = false,
        string $customConstructorArgumentName = null
    ): self;
}
