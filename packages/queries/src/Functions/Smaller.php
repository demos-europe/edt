<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

/**
 * @template-extends AbstractMultiFunction<bool, mixed, array{mixed, mixed}>
 */
class Smaller extends AbstractMultiFunction
{
    protected function reduce(array $functionResults): bool
    {
        [$left, $right] = $functionResults;
        return $left < $right;
    }
}
