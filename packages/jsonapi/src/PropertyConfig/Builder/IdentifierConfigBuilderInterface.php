<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Wrapping\PropertyBehavior\Identifier\Factory\IdentifierConstructorBehaviorFactoryInterface;
use EDT\Wrapping\PropertyBehavior\Identifier\Factory\IdentifierPostConstructorBehaviorFactoryInterface;

/**
 * @template TEntity of object
 */
interface IdentifierConfigBuilderInterface extends PropertyConfigBuilderInterface
{
    /**
     * @param null|callable(TEntity): non-empty-string $customReadCallback
     *
     * @return $this
     */
    public function readable(callable $customReadCallback = null): self;

    /**
     * @param IdentifierConstructorBehaviorFactoryInterface<TEntity> $behaviorFactory
     *
     * @return $this
     */
    public function addConstructorBehavior(IdentifierConstructorBehaviorFactoryInterface $behaviorFactory): self;

    /**
     * @param IdentifierPostConstructorBehaviorFactoryInterface<TEntity> $behaviorFactory
     *
     * @return $this
     */
    public function addPostConstructorBehavior(IdentifierPostConstructorBehaviorFactoryInterface $behaviorFactory): self;

    /**
     * @param non-empty-string|null $customConstructorArgumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     *
     * @return $this
     */
    public function initializable(
        bool $optionalAfterConstructor = false,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null
    ): self;
}
