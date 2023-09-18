<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 * @template TValue
 */
interface InstantiablePropertyConfigBuilderInterface
{
    /**
     * @param null|callable(TEntity, TValue): bool $postInstantiationCallback
     * @param bool $argument `true` if the value of this property is expected as constructor parameter of the corresponding entity
     * @param non-empty-string|null $argumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     *
     * @return $this
     */
    public function instantiable(
        bool $optional = false,
        callable $postInstantiationCallback = null,
        bool $argument = false,
        ?string $argumentName = null
    ): self;
}
