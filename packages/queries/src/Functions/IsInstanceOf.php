<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

/**
 * @template-extends AbstractMultiFunction<bool, non-empty-string, array{non-empty-string, class-string}>
 */
class IsInstanceOf extends AbstractMultiFunction
{
    protected function reduce(array $functionResults): bool
    {
        [$left, $right] = $functionResults;
        return $left instanceof $right;
    }
}
