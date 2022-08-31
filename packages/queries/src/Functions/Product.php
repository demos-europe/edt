<?php

declare(strict_types=1);

namespace EDT\Querying\Functions;

/**
 * @template-extends AbstractMultiFunction<numeric, numeric, array<int, mixed>>
 */
class Product extends AbstractMultiFunction
{
    protected function reduce(array $functionResults)
    {
        return array_product($functionResults);
    }
}
