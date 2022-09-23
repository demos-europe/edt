<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * Some kind of function with input parameters and an output of type `R`.
 *
 * Template parameters:
 *
 * * `R`: the possible return types of this function
 *
 * @template R
 */
interface FunctionInterface extends PathsBasedInterface
{
    /**
     * Execute this function within PHP.
     *
     * @param list<mixed> $propertyValues
     * @return R
     * @throws FunctionException
     */
    public function apply(array $propertyValues);

    public function __toString(): string;
}
