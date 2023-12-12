<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

class IsInstanceOf extends AbstractMultiFunction
{
    protected function reduce(array $functionResults): bool
    {
        [$left, $right] = $functionResults;
        return $left instanceof $right;
    }
}
