<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements PropertySetabilityInterface<TCondition, TEntity>
 */
abstract class AbstractSetability implements PropertySetabilityInterface
{
    /**
     * @param non-empty-string $propertyName the exposed property name accepted by this instance
     */
    public function __construct(
        protected readonly string $propertyName,
        protected readonly bool $optional
    ) {}
}
