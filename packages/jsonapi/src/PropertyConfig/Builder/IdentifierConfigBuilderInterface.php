<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

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
     * @param non-empty-string|null $argumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     *
     * @return $this
     */
    public function instantiable(bool $postInstantiationSetting, bool $argument = false, string $argumentName = null);
}
