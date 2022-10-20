<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface ConditionGroupFactoryInterface
{
    /**
     * The returned condition will evaluate to `true` if the property denoted by
     * the given path has a value assigned that is present in the given
     * array of values.
     *
     * @param list<mixed> $values
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasAnyOfValues(array $values, string $property, string ...$properties): PathsBasedInterface;

    /**
     * @param list<mixed> $values
     * @param non-empty-string $property
     * @param non-empty-string ...$properties
     *
     * @return TCondition
     *
     * @throws PathException
     */
    public function propertyHasNotAnyOfValues(array $values, string $property, string ...$properties): PathsBasedInterface;
}
