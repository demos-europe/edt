<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

/**
 * @template-extends AbstractMultiFunction<numeric, numeric, list<numeric>>
 */
class Sum extends AbstractMultiFunction
{
    protected function reduce(array $functionResults)
    {
        return array_sum($functionResults);
    }
}
