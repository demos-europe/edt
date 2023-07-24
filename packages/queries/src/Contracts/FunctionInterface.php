<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * Some kind of function with input parameters and an output of type `TOutput`.
 *
 * @template TOutput
 */
interface FunctionInterface extends PathsBasedInterface
{
    /**
     * Execute this function within PHP.
     *
     * @param list<mixed> $propertyValues
     * @return TOutput
     */
    public function apply(array $propertyValues);

    public function __toString(): string;
}
