<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

/**
 * @template-extends AbstractMultiFunction<bool, mixed|null, array{mixed|null, mixed|null, mixed|null}>
 */
class BetweenInclusive extends AbstractMultiFunction
{
    protected function reduce(array $functionResults): bool
    {
        [$min, $max, $value] = $functionResults;
        if (null === $min || null === $max || null === $value) {
            return false;
        }
        return $value >= $min && $value <= $max;
    }
}
