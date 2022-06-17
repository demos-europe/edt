<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

/**
 * Some kind of function with input parameters and an output of type `R`.
 *
 * @template R
 */
interface FunctionInterface extends PathsBasedInterface
{
    /**
     * Execute this function within PHP.
     *
     * @param mixed[] $propertyValues
     * @return R
     */
    public function apply(array $propertyValues);

    public function __toString(): string;
}
