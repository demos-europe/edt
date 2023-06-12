<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

trait SideEffectHandleTrait
{
    /**
     * @param list<list<bool>> $nestedSideEffects
     */
    protected function mergeSideEffects(array $nestedSideEffects): bool
    {
        return array_reduce(
            $nestedSideEffects,
            static fn (bool $outerCarry, array $sideEffects): bool => array_reduce(
                $sideEffects,
                static fn (bool $innerCarry, bool $sideEffect): bool => $innerCarry || $sideEffect,
                $outerCarry
            ),
            false
        );
    }
}
