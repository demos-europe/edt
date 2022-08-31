<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

/**
 * @template-extends AbstractMultiFunction<bool, mixed, array{0: mixed, 1: mixed}>
 */
class GreaterEquals extends AbstractMultiFunction
{
    protected function reduce(array $functionResults): bool
    {
        [$left, $right] = $functionResults;
        return $left >= $right;
    }
}
